<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\{Payment, Subscription, Wallet, WalletTransaction, UserDevice, Plan, PromoCode};
use App\Services\{NavidromeService, StripeService};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Hash, Log, DB};

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $pendingSub = Subscription::where('user_id', $user->id)->where('status', 'pending')->with('plan')->latest()->first();
        return view('portal.dashboard', [
            'activeSub' => $user->activeSubscription?->load('plan'),
            'pendingSub' => $pendingSub,
            'wallet' => $user->wallet,
            'recentPayments' => Payment::where('user_id', $user->id)->latest()->take(5)->get(),
        ]);
    }

    public function profile(Request $request, NavidromeService $nd)
    {
        if ($request->isMethod('post')) {
            $data = $request->validate(['first_name' => 'nullable|max:100', 'last_name' => 'nullable|max:100', 'email' => 'required|email', 'phone' => 'nullable|max:20']);
            $request->user()->update($data);
            if ($request->user()->navidrome_id) {
                try { $nd->updateUser($request->user()->navidrome_id, ['name' => $request->user()->full_name, 'email' => $data['email']]); } catch (\Exception $e) {}
            }
            return back()->with('success', 'Profil mis à jour.');
        }
        return view('portal.profile');
    }

    public function changePassword(Request $request, NavidromeService $nd)
    {
        if ($request->isMethod('post')) {
            $request->validate(['current_password' => 'required', 'password' => 'required|min:6|confirmed']);
            if (!Hash::check($request->current_password, $request->user()->password)) {
                return back()->withErrors(['current_password' => 'Mot de passe actuel incorrect.']);
            }
            $request->user()->update(['password' => Hash::make($request->password)]);
            $request->user()->storeEncryptedPassword($request->password);
            if ($request->user()->navidrome_id) {
                try { $nd->changePassword($request->user()->navidrome_id, $request->password); } catch (\Exception $e) {}
            }
            return redirect('/login')->with('success', 'Mot de passe modifié. Reconnectez-vous.');
        }
        return view('portal.change-password');
    }

    public function plans()
    {
        return view('portal.plans', [
            'plans' => Plan::where('is_active', true)->orderBy('sort_order')->get(),
            'activeSub' => Auth::user()->activeSubscription,
        ]);
    }

    public function subscribe(string $planId, Request $request, StripeService $stripe)
    {
        $plan = Plan::where('is_active', true)->findOrFail($planId);
        $user = Auth::user();
        if ($user->activeSubscription) return redirect('/portal')->with('error', 'Vous avez déjà un abonnement actif.');

        // Durée de prépaiement (1 = abonnement récurrent standard, 3/6/12 = paiement unique prépayé)
        $months = (int) $request->input('months', 1);
        if (!in_array($months, [1, 3, 6, 12], true)) $months = 1;

        $promo = null;
        if ($code = $request->query('promo')) {
            $promo = PromoCode::where('code', $code)->first();
            if ($promo && !$promo->is_valid) $promo = null;
        }

        if ($months === 1) {
            // Abonnement récurrent classique via Stripe Subscription
            if (!$plan->stripe_price_id || !str_starts_with($plan->stripe_price_id, 'price_')) {
                Log::error("Plan {$plan->id} ({$plan->name}) has an invalid stripe_price_id: " . ($plan->stripe_price_id ?? 'null'));
                return redirect('/portal/plans')->with('error', 'Cette formule est mal configurée (Stripe Price ID manquant ou invalide). Merci de contacter le support.');
            }

            try {
                $session = $stripe->createCheckoutSession($user, $plan,
                    url('/payments/success?session_id={CHECKOUT_SESSION_ID}'),
                    url('/portal/plans'));
            } catch (\Exception $e) {
                Log::error("Stripe checkout failed for user {$user->id} plan {$plan->id}: {$e->getMessage()}");
                return redirect('/portal/plans')->with('error', 'Impossible de démarrer le paiement. Merci de réessayer ou de contacter le support.');
            }

            // Supprimer les pending orphelins puis créer le nouveau pending
            Subscription::where('user_id', $user->id)->where('status', 'pending')->delete();
            Subscription::create(['user_id' => $user->id, 'plan_id' => $plan->id, 'status' => 'pending', 'promo_code_id' => $promo?->id]);
            return redirect($session->url);
        }

        // Paiement unique pour N mois d'avance (pas de Stripe Subscription, juste un one-shot)
        try {
            $session = $stripe->createPrepaySession($user, $plan, $months,
                url('/payments/success?session_id={CHECKOUT_SESSION_ID}'),
                url('/portal/plans'));
        } catch (\Exception $e) {
            Log::error("Stripe prepay checkout failed for user {$user->id} plan {$plan->id}: {$e->getMessage()}");
            return redirect('/portal/plans')->with('error', 'Impossible de démarrer le paiement. Merci de réessayer ou de contacter le support.');
        }

        Subscription::where('user_id', $user->id)->where('status', 'pending')->delete();
        Subscription::create(['user_id' => $user->id, 'plan_id' => $plan->id, 'status' => 'pending', 'promo_code_id' => $promo?->id]);
        return redirect($session->url);
    }

    public function resumePayment(Request $request, StripeService $stripe)
    {
        $user = Auth::user();
        $sub = Subscription::where('user_id', $user->id)->where('status', 'pending')->with('plan')->latest()->first();
        if (!$sub) return redirect('/portal')->with('error', 'Aucun paiement en attente.');
        if ($user->activeSubscription) return redirect('/portal')->with('error', 'Vous avez déjà un abonnement actif.');

        $months = (int) $request->input('months', 1);
        if (!in_array($months, [1, 3, 6, 12], true)) $months = 1;

        try {
            if ($months === 1) {
                if (!$sub->plan->stripe_price_id || !str_starts_with($sub->plan->stripe_price_id, 'price_')) {
                    return redirect('/portal/plans')->with('error', 'Formule mal configurée. Contactez le support.');
                }
                $session = $stripe->createCheckoutSession($user, $sub->plan,
                    url('/payments/success?session_id={CHECKOUT_SESSION_ID}'),
                    url('/portal'));
            } else {
                $session = $stripe->createPrepaySession($user, $sub->plan, $months,
                    url('/payments/success?session_id={CHECKOUT_SESSION_ID}'),
                    url('/portal'));
            }
        } catch (\Exception $e) {
            Log::error("Stripe resume failed for user {$user->id}: {$e->getMessage()}");
            return redirect('/portal')->with('error', 'Impossible de reprendre le paiement. Merci de réessayer.');
        }

        return redirect($session->url);
    }

    public function cancelSubscription(Request $request, StripeService $stripe)
    {
        $sub = $request->user()->activeSubscription;
        if (!$sub) return redirect('/portal')->with('error', 'Aucun abonnement actif.');
        if ($sub->stripe_subscription_id) {
            try { $stripe->cancelSubscription($sub->stripe_subscription_id); } catch (\Exception $e) {}
        }
        $sub->update(['status' => 'cancelled', 'cancelled_at' => now()]);
        return redirect('/portal')->with('success', 'Abonnement annulé en fin de période.');
    }

    public function wallet()
    {
        $wallet = Auth::user()->wallet ?? Wallet::create(['user_id' => Auth::id()]);
        return view('portal.wallet', [
            'wallet' => $wallet,
            'transactions' => WalletTransaction::where('wallet_id', $wallet->id)->latest('created_at')->take(30)->get(),
        ]);
    }

    public function walletTopup(Request $request, StripeService $stripe)
    {
        $amount = (float) $request->input('amount', 10);
        if ($amount < 5) return back()->with('error', 'Minimum 5€.');
        $session = $stripe->createWalletTopupSession(Auth::user(), (int)($amount * 100),
            url('/payments/wallet-success?session_id={CHECKOUT_SESSION_ID}'), url('/portal/wallet'));
        return redirect($session->url);
    }

    public function gift(Request $request, StripeService $stripe)
    {
        $plans = Plan::where('is_active', true)->get();
        if ($request->isMethod('post')) {
            $request->validate(['plan_id' => 'required|exists:plans,id', 'recipient_email' => 'required|email']);
            $plan = Plan::findOrFail($request->plan_id);
            $session = $stripe->createGiftSession(Auth::user(), $plan, $request->recipient_email,
                url('/payments/gift-success?session_id={CHECKOUT_SESSION_ID}'), url('/portal/gift'));
            return redirect($session->url);
        }
        return view('portal.gift', compact('plans'));
    }

    public function devices()
    {
        return view('portal.devices', ['devices' => UserDevice::where('user_id', Auth::id())->where('is_active', true)->get()]);
    }

    public function revokeDevice(string $id)
    {
        UserDevice::where('id', $id)->where('user_id', Auth::id())->update(['is_active' => false]);
        return back()->with('success', 'Accès révoqué.');
    }

    public function payments()
    {
        return view('portal.payments', ['payments' => Payment::where('user_id', Auth::id())->latest()->paginate(20)]);
    }
}
