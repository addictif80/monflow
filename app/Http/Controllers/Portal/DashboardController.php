<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\{Payment, Subscription, Wallet, WalletTransaction, UserDevice, Plan, PromoCode, Notification, Ticket, Feedback};
use App\Services\{NavidromeService, StripeService, EmailService};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Hash, Log, DB};
use Symfony\Component\HttpFoundation\StreamedResponse;

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
            'unreadNotifications' => Notification::where('user_id', $user->id)->unread()->count(),
        ]);
    }

    public function profile(Request $request, NavidromeService $nd)
    {
        if ($request->isMethod('post')) {
            $data = $request->validate(['first_name' => 'nullable|max:100', 'last_name' => 'nullable|max:100', 'email' => 'required|email', 'phone' => 'nullable|max:20', 'newsletter_optin' => 'nullable']);
            $data['newsletter_optin'] = (bool) ($data['newsletter_optin'] ?? false);
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

        $months = (int) $request->input('months', 1);
        if (!in_array($months, [1, 3, 6, 12], true)) $months = 1;

        $promo = null;
        if ($code = $request->query('promo')) {
            $promo = PromoCode::where('code', $code)->first();
            if ($promo && !$promo->is_valid) $promo = null;
        }

        if ($months === 1) {
            // Abonnement récurrent Stripe (avec ou sans coupon promo)
            if (!$plan->stripe_price_id || !str_starts_with($plan->stripe_price_id, 'price_')) {
                Log::error("Plan {$plan->id} ({$plan->name}) has an invalid stripe_price_id: " . ($plan->stripe_price_id ?? 'null'));
                return redirect('/portal/plans')->with('error', 'Cette formule est mal configurée (Stripe Price ID manquant ou invalide). Merci de contacter le support.');
            }

            try {
                $session = $stripe->createCheckoutSession($user, $plan,
                    url('/payments/success?session_id={CHECKOUT_SESSION_ID}'),
                    url('/portal/plans'),
                    $promo);
            } catch (\Exception $e) {
                Log::error("Stripe checkout failed for user {$user->id} plan {$plan->id}: {$e->getMessage()}");
                return redirect('/portal/plans')->with('error', 'Impossible de démarrer le paiement. Merci de réessayer ou de contacter le support.');
            }

            if ($promo) $promo->increment('current_uses');
            Subscription::where('user_id', $user->id)->where('status', 'pending')->delete();
            Subscription::create(['user_id' => $user->id, 'plan_id' => $plan->id, 'status' => 'pending', 'promo_code_id' => $promo?->id]);
            return redirect($session->url);
        }

        // Paiement unique prépayé (N mois d'avance, avec réduction éventuelle)
        $baseAmount = $plan->price * $months;
        $discount = 0;
        if ($promo) {
            $discount = $promo->discount_type === 'percentage'
                ? round($baseAmount * $promo->discount_value / 100, 2)
                : min($promo->discount_value, $baseAmount);
        }
        $finalAmount = max(0, $baseAmount - $discount);

        try {
            $session = $stripe->createPrepaySession($user, $plan, $months,
                url('/payments/success?session_id={CHECKOUT_SESSION_ID}'),
                url('/portal/plans'),
                $finalAmount);
        } catch (\Exception $e) {
            Log::error("Stripe prepay checkout failed for user {$user->id} plan {$plan->id}: {$e->getMessage()}");
            return redirect('/portal/plans')->with('error', 'Impossible de démarrer le paiement. Merci de réessayer ou de contacter le support.');
        }

        if ($promo) $promo->increment('current_uses');
        Subscription::where('user_id', $user->id)->where('status', 'pending')->delete();
        Subscription::create(['user_id' => $user->id, 'plan_id' => $plan->id, 'status' => 'pending', 'promo_code_id' => $promo?->id]);
        return redirect($session->url);
    }

    public function resumePayment(Request $request, StripeService $stripe)
    {
        $user = Auth::user();
        $sub = Subscription::where('user_id', $user->id)->where('status', 'pending')->with('plan', 'promoCode')->latest()->first();
        if (!$sub) return redirect('/portal')->with('error', 'Aucun paiement en attente.');
        if ($user->activeSubscription) return redirect('/portal')->with('error', 'Vous avez déjà un abonnement actif.');

        $months = (int) $request->input('months', 1);
        if (!in_array($months, [1, 3, 6, 12], true)) $months = 1;

        $promo = ($sub->promoCode && $sub->promoCode->is_valid) ? $sub->promoCode : null;

        try {
            if ($months === 1) {
                if (!$sub->plan->stripe_price_id || !str_starts_with($sub->plan->stripe_price_id, 'price_')) {
                    return redirect('/portal/plans')->with('error', 'Formule mal configurée. Contactez le support.');
                }
                $session = $stripe->createCheckoutSession($user, $sub->plan,
                    url('/payments/success?session_id={CHECKOUT_SESSION_ID}'),
                    url('/portal'),
                    $promo);
            } else {
                $baseAmount = $sub->plan->price * $months;
                $discount = 0;
                if ($promo) {
                    $discount = $promo->discount_type === 'percentage'
                        ? round($baseAmount * $promo->discount_value / 100, 2)
                        : min($promo->discount_value, $baseAmount);
                }
                $session = $stripe->createPrepaySession($user, $sub->plan, $months,
                    url('/payments/success?session_id={CHECKOUT_SESSION_ID}'),
                    url('/portal'),
                    max(0, $baseAmount - $discount));
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

    public function walletPay(Request $request, NavidromeService $nd)
    {
        $user = Auth::user();
        if ($user->activeSubscription) return redirect('/portal')->with('error', 'Vous avez déjà un abonnement actif.');

        $plan = Plan::where('is_active', true)->findOrFail($request->input('plan_id'));
        $months = (int) $request->input('months', 1);
        if (!in_array($months, [1, 3, 6, 12], true)) $months = 1;

        $baseTotal = $plan->price * $months;
        $promo = null;
        $discount = 0;
        if ($code = $request->input('promo')) {
            $promo = PromoCode::where('code', $code)->first();
            if ($promo && $promo->is_valid) {
                $discount = $promo->discount_type === 'percentage'
                    ? round($baseTotal * $promo->discount_value / 100, 2)
                    : min($promo->discount_value, $baseTotal);
            } else {
                $promo = null;
            }
        }
        $total = max(0, $baseTotal - $discount);
        $wallet = $user->wallet ?? Wallet::create(['user_id' => $user->id]);

        if ($wallet->balance < $total) {
            return back()->with('error', "Solde insuffisant ({$wallet->balance}€). Rechargez votre portefeuille.");
        }

        if ($promo) $promo->increment('current_uses');

        $description = "{$plan->name} — {$months} mois";
        if ($promo) $description .= " (code {$promo->code} : -{$discount}€)";

        DB::transaction(function () use ($user, $plan, $months, $total, $wallet, $nd, $promo, $description) {
            $wallet = Wallet::lockForUpdate()->find($wallet->id);
            $wallet->decrement('balance', $total);
            WalletTransaction::create(['wallet_id' => $wallet->id, 'type' => 'payment', 'amount' => -$total, 'description' => $description]);

            $days = $plan->period_days * $months;
            $sub = Subscription::where('user_id', $user->id)->whereIn('status', ['active', 'pending'])->latest()->first();
            $end = ($sub && $sub->current_period_end && $sub->current_period_end->isFuture())
                ? $sub->current_period_end->copy()->addDays($days)
                : now()->addDays($days);

            if ($sub) {
                $sub->update(['plan_id' => $plan->id, 'status' => 'active', 'current_period_start' => $sub->current_period_start ?? now(), 'current_period_end' => $end]);
            } else {
                $sub = Subscription::create(['user_id' => $user->id, 'plan_id' => $plan->id, 'status' => 'active', 'current_period_start' => now(), 'current_period_end' => $end]);
            }

            Payment::create(['user_id' => $user->id, 'subscription_id' => $sub->id, 'amount' => $total, 'stripe_amount' => 0, 'status' => 'succeeded', 'payment_method' => 'wallet', 'description' => "{$plan->name} — {$months} mois (portefeuille)"]);

            Notification::send($user->id, 'payment_success', 'Paiement confirmé', "Votre abonnement {$plan->name} ({$months} mois) a été activé via le portefeuille.", '/portal');

            if ($user->status === 'suspended') $user->update(['status' => 'active']);
            if ($user->navidrome_id) {
                $pw = $user->getDecryptedPassword();
                if ($pw) { try { $nd->reactivateUser($user->navidrome_id, $pw); } catch (\Exception $e) { Log::error($e->getMessage()); } }
            }
        });

        Subscription::where('user_id', $user->id)->where('status', 'pending')->delete();

        return redirect('/portal')->with('success', "Abonnement {$plan->name} activé pour {$months} mois.");
    }

    public function notifications()
    {
        $notifications = Notification::where('user_id', Auth::id())->latest()->paginate(30);
        return view('portal.notifications', compact('notifications'));
    }

    public function markNotificationsRead()
    {
        Notification::where('user_id', Auth::id())->unread()->update(['read_at' => now()]);
        return back()->with('success', 'Notifications marquées comme lues.');
    }

    public function invoice(string $id)
    {
        $payment = Payment::where('user_id', Auth::id())->findOrFail($id);
        return view('portal.invoice', ['payment' => $payment, 'user' => Auth::user()]);
    }

    public function cancelSubscriptionConfirm()
    {
        $sub = Auth::user()->activeSubscription?->load('plan');
        if (!$sub) return redirect('/portal')->with('error', 'Aucun abonnement actif.');
        return view('portal.cancel-subscription', ['sub' => $sub]);
    }

    public function exportData(Request $request)
    {
        $user = Auth::user();

        $wallet = $user->wallet;
        $transactions = $wallet
            ? WalletTransaction::where('wallet_id', $wallet->id)->orderBy('created_at')->get(['type', 'amount', 'description', 'created_at'])
            : collect();

        $tickets = Ticket::where('user_id', $user->id)->with(['messages' => fn($q) => $q->select('ticket_id', 'body', 'is_staff_reply', 'created_at')])->get(['id', 'subject', 'status', 'created_at']);

        $data = [
            'export_date' => now()->toIso8601String(),
            'profil' => [
                'username'   => $user->username,
                'email'      => $user->email,
                'prenom'     => $user->first_name,
                'nom'        => $user->last_name,
                'telephone'  => $user->phone,
                'newsletter' => $user->newsletter_optin,
                'statut'     => $user->status,
                'inscription'=> $user->created_at?->toIso8601String(),
            ],
            'abonnements' => Subscription::where('user_id', $user->id)->with('plan:id,name,price,billing_cycle')->get(['plan_id', 'status', 'current_period_start', 'current_period_end', 'cancelled_at', 'created_at']),
            'paiements' => Payment::where('user_id', $user->id)->get(['amount', 'status', 'payment_method', 'description', 'created_at']),
            'portefeuille' => [
                'solde' => $wallet?->balance ?? 0,
                'transactions' => $transactions,
            ],
            'tickets_support' => $tickets,
            'feedbacks' => Feedback::where('user_id', $user->id)->get(['type', 'subject', 'body', 'status', 'created_at']),
            'appareils' => UserDevice::where('user_id', $user->id)->get(['device_name', 'ip_address', 'last_seen_at', 'is_active', 'created_at']),
        ];

        $filename = 'monflow-export-' . now()->format('Y-m-d') . '.json';
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return response($json, 200, [
            'Content-Type'        => 'application/json',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function deleteAccount(Request $request, NavidromeService $nd, StripeService $stripe, EmailService $mail)
    {
        $user = Auth::user();

        if ($request->isMethod('get')) {
            return view('portal.delete-account');
        }

        $request->validate([
            'password' => 'required',
            'confirm'  => 'required|in:SUPPRIMER',
        ], [
            'confirm.in' => 'Tapez exactement SUPPRIMER pour confirmer.',
        ]);

        if (!Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'Mot de passe incorrect.']);
        }

        // Annuler les abonnements Stripe actifs
        foreach (Subscription::where('user_id', $user->id)->whereNotNull('stripe_subscription_id')->where('stripe_subscription_id', '!=', '')->get() as $sub) {
            try { $stripe->cancelSubscriptionNow($sub->stripe_subscription_id); } catch (\Exception $e) {}
        }

        // Supprimer le compte Navidrome
        if ($user->navidrome_id) {
            try { $nd->deleteUser($user->navidrome_id); } catch (\Exception $e) {
                Log::error("Navidrome delete failed for user {$user->id}: {$e->getMessage()}");
            }
        }

        // Envoyer l'email de confirmation avant d'anonymiser
        try { $mail->sendDeleted($user); } catch (\Exception $e) {}

        // Déconnexion immédiate
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Anonymiser les données personnelles (les paiements sont conservés pour obligation légale)
        DB::transaction(function () use ($user) {
            $ts = now()->timestamp;
            $user->update([
                'status'             => 'deleted',
                'email'              => "deleted_{$ts}_{$user->id}@deleted.invalid",
                'first_name'         => null,
                'last_name'          => null,
                'phone'              => null,
                'newsletter_optin'   => false,
                'navidrome_id'       => null,
                'encrypted_password' => null,
            ]);

            Subscription::where('user_id', $user->id)->whereNotIn('status', ['cancelled', 'expired'])->update(['status' => 'cancelled', 'cancelled_at' => now()]);
            UserDevice::where('user_id', $user->id)->delete();
            Notification::where('user_id', $user->id)->delete();
        });

        return redirect('/login')->with('success', 'Votre compte a été supprimé. Vos données personnelles ont été effacées.');
    }
}
