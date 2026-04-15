<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{User, Wallet, WalletTransaction, Subscription, Plan, PromoCode, Payment, Refund, Ticket, TicketMessage, SmtpConfiguration, EmailTemplate};
use App\Services\{NavidromeService, StripeService, EmailService};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Hash, DB, Log};

class AdminController extends Controller
{
    // ─── Dashboard ───
    public function dashboard()
    {
        return view('admin.dashboard', [
            'totalUsers' => User::where('is_admin', false)->count(),
            'activeUsers' => User::where('status', 'active')->where('is_admin', false)->count(),
            'suspendedUsers' => User::where('status', 'suspended')->count(),
            'activeSubs' => Subscription::where('status', 'active')->count(),
            'revenueMonth' => Payment::where('status', 'succeeded')->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('amount'),
            'openTickets' => Ticket::whereIn('status', ['open', 'in_progress'])->count(),
            'recentPayments' => Payment::with('user')->latest()->take(10)->get(),
            'recentTickets' => Ticket::with('user')->latest()->take(5)->get(),
        ]);
    }

    // ─── Users ───
    public function users(Request $request)
    {
        $q = User::where('is_admin', false);
        if ($s = $request->input('status')) $q->where('status', $s);
        if ($search = $request->input('q')) $q->where(fn($q) => $q->where('username', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")->orWhere('first_name', 'like', "%{$search}%"));
        return view('admin.users.list', ['users' => $q->latest()->paginate(25), 'status' => $s, 'search' => $search]);
    }

    public function userCreate(Request $request, NavidromeService $nd, EmailService $mail)
    {
        if ($request->isMethod('post')) {
            $data = $request->validate(['username' => 'required|unique:users', 'email' => 'required|email|unique:users', 'first_name' => 'nullable', 'last_name' => 'nullable', 'phone' => 'nullable', 'password' => 'required|min:6', 'is_admin' => 'nullable']);
            $user = User::create([...$data, 'password' => Hash::make($data['password']), 'is_admin' => (bool)($data['is_admin'] ?? false)]);
            $user->storeEncryptedPassword($data['password']);
            Wallet::create(['user_id' => $user->id]);
            try { $r = $nd->createUser($user->username, $data['password'], $user->full_name, $user->email); $user->update(['navidrome_id' => $r['id'] ?? null]); } catch (\Exception $e) { Log::error($e->getMessage()); }
            try { $mail->sendWelcome($user); } catch (\Exception $e) {}
            return redirect('/admin/users')->with('success', "Utilisateur {$user->username} créé.");
        }
        return view('admin.users.form', ['title' => 'Nouvel utilisateur', 'user' => null]);
    }

    public function userEdit(string $id, Request $request, NavidromeService $nd)
    {
        $user = User::findOrFail($id);
        if ($request->isMethod('post')) {
            $data = $request->validate(['username' => "required|unique:users,username,{$id}", 'email' => "required|email|unique:users,email,{$id}", 'first_name' => 'nullable', 'last_name' => 'nullable', 'phone' => 'nullable', 'status' => 'required', 'password' => 'nullable|min:6', 'is_admin' => 'nullable']);
            $user->fill($data);
            $user->is_admin = (bool)($data['is_admin'] ?? false);
            if (!empty($data['password'])) {
                $user->password = Hash::make($data['password']);
                $user->storeEncryptedPassword($data['password']);
                if ($user->navidrome_id) { try { $nd->changePassword($user->navidrome_id, $data['password']); } catch (\Exception $e) {} }
            }
            $user->save();
            return redirect('/admin/users')->with('success', 'Utilisateur mis à jour.');
        }
        return view('admin.users.form', ['title' => "Modifier {$user->username}", 'user' => $user]);
    }

    public function userDetail(string $id)
    {
        $user = User::findOrFail($id);
        return view('admin.users.detail', [
            'user' => $user, 'wallet' => $user->wallet,
            'subscriptions' => Subscription::where('user_id', $id)->with('plan')->get(),
            'payments' => Payment::where('user_id', $id)->latest()->take(20)->get(),
        ]);
    }

    public function userSuspend(string $id, NavidromeService $nd, EmailService $mail)
    {
        $user = User::findOrFail($id);
        $user->update(['status' => 'suspended']);
        Subscription::where('user_id', $id)->where('status', 'active')->update(['status' => 'suspended']);
        if ($user->navidrome_id) { try { $nd->suspendUser($user->navidrome_id); } catch (\Exception $e) { Log::error($e->getMessage()); } }
        try { $mail->sendSuspended($user); } catch (\Exception $e) {}
        return back()->with('success', "Utilisateur {$user->username} suspendu.");
    }

    public function userReactivate(string $id, NavidromeService $nd)
    {
        $user = User::findOrFail($id);
        $user->update(['status' => 'active']);
        // Restaurer le mot de passe original sur Navidrome
        if ($user->navidrome_id) {
            $originalPassword = $user->getDecryptedPassword();
            if ($originalPassword) {
                try { $nd->reactivateUser($user->navidrome_id, $originalPassword); } catch (\Exception $e) { Log::error($e->getMessage()); }
            }
        }
        return back()->with('success', "Utilisateur {$user->username} réactivé avec son mot de passe original.");
    }

    public function userDelete(string $id, NavidromeService $nd, StripeService $stripe, EmailService $mail)
    {
        $user = User::findOrFail($id);
        try { $mail->sendDeleted($user); } catch (\Exception $e) {}
        if ($user->navidrome_id) { try { $nd->deleteUser($user->navidrome_id); } catch (\Exception $e) {} }
        foreach (Subscription::where('user_id', $id)->whereNotNull('stripe_subscription_id')->where('stripe_subscription_id', '!=', '')->get() as $sub) {
            try { $stripe->cancelSubscriptionNow($sub->stripe_subscription_id); } catch (\Exception $e) {}
        }
        $user->update(['status' => 'deleted']);
        return redirect('/admin/users')->with('success', "Utilisateur supprimé.");
    }

    public function walletAdjust(string $userId, Request $request)
    {
        $request->validate(['amount' => 'required|numeric', 'description' => 'nullable']);
        $wallet = Wallet::firstOrCreate(['user_id' => $userId]);
        DB::transaction(function () use ($wallet, $request) {
            $wallet = Wallet::lockForUpdate()->find($wallet->id);
            $wallet->increment('balance', $request->amount);
            WalletTransaction::create(['wallet_id' => $wallet->id, 'type' => 'adjustment', 'amount' => $request->amount, 'description' => $request->input('description', 'Ajustement admin')]);
        });
        return back()->with('success', "Portefeuille ajusté de {$request->amount}€.");
    }

    // ─── Plans ───
    public function plans() { return view('admin.plans.list', ['plans' => Plan::orderBy('sort_order')->get()]); }
    public function planCreate(Request $request)
    {
        if ($request->isMethod('post')) { Plan::create($request->validate(['name' => 'required', 'description' => 'nullable', 'price' => 'required|numeric', 'billing_cycle' => 'required', 'stripe_price_id' => 'nullable', 'max_devices' => 'required|integer', 'sort_order' => 'nullable|integer'])); return redirect('/admin/plans')->with('success', 'Formule créée.'); }
        return view('admin.plans.form', ['title' => 'Nouvelle formule', 'plan' => null]);
    }
    public function planEdit(string $id, Request $request)
    {
        $plan = Plan::findOrFail($id);
        if ($request->isMethod('post')) { $plan->update($request->validate(['name' => 'required', 'description' => 'nullable', 'price' => 'required|numeric', 'billing_cycle' => 'required', 'stripe_price_id' => 'nullable', 'max_devices' => 'required|integer', 'is_active' => 'nullable', 'sort_order' => 'nullable|integer'])); return redirect('/admin/plans')->with('success', 'Formule mise à jour.'); }
        return view('admin.plans.form', ['title' => "Modifier {$plan->name}", 'plan' => $plan]);
    }

    // ─── Promos ───
    public function promos() { return view('admin.promos.list', ['promos' => PromoCode::latest('created_at')->get()]); }
    public function promoCreate(Request $request)
    {
        if ($request->isMethod('post')) { PromoCode::create($request->validate(['code' => 'required|unique:promo_codes', 'discount_type' => 'required', 'discount_value' => 'required|numeric', 'max_uses' => 'nullable|integer', 'valid_from' => 'required|date', 'valid_until' => 'nullable|date'])); return redirect('/admin/promos')->with('success', 'Code promo créé.'); }
        return view('admin.promos.form', ['title' => 'Nouveau code promo', 'promo' => null]);
    }
    public function promoEdit(string $id, Request $request)
    {
        $promo = PromoCode::findOrFail($id);
        if ($request->isMethod('post')) { $promo->update($request->validate(['code' => "required|unique:promo_codes,code,{$id}", 'discount_type' => 'required', 'discount_value' => 'required|numeric', 'max_uses' => 'nullable|integer', 'valid_from' => 'required|date', 'valid_until' => 'nullable|date', 'is_active' => 'nullable'])); return redirect('/admin/promos')->with('success', 'Code mis à jour.'); }
        return view('admin.promos.form', ['title' => "Modifier {$promo->code}", 'promo' => $promo]);
    }

    // ─── Payments & Refunds ───
    public function payments() { return view('admin.payments.list', ['payments' => Payment::with('user')->latest()->paginate(25)]); }
    public function refunds() { return view('admin.refunds.list', ['refunds' => Refund::with('payment.user', 'processedBy')->latest()->paginate(25)]); }

    public function refundCreate(string $paymentId, Request $request, StripeService $stripe, EmailService $mail)
    {
        $payment = Payment::with('user')->findOrFail($paymentId);
        if ($request->isMethod('post')) {
            $amount = (float) $request->input('amount', $payment->amount);
            $refund = Refund::create(['payment_id' => $payment->id, 'amount' => $amount, 'reason' => $request->input('reason', ''), 'refund_to' => $request->input('refund_to', 'original'), 'processed_by' => auth()->id()]);
            try {
                if ($refund->refund_to === 'original' && $payment->stripe_payment_intent_id) {
                    $sr = $stripe->createRefund($payment->stripe_payment_intent_id, $amount < $payment->amount ? (int)($amount * 100) : null);
                    $refund->update(['stripe_refund_id' => $sr->id, 'status' => 'processed']);
                } elseif ($refund->refund_to === 'wallet') {
                    DB::transaction(function () use ($payment, $amount) {
                        $wallet = Wallet::lockForUpdate()->firstOrCreate(['user_id' => $payment->user_id]);
                        $wallet->increment('balance', $amount);
                        WalletTransaction::create(['wallet_id' => $wallet->id, 'type' => 'refund', 'amount' => $amount, 'description' => "Remboursement"]);
                    });
                    $refund->update(['status' => 'processed']);
                }
                $payment->update(['status' => $amount >= $payment->amount ? 'refunded' : 'partially_refunded']);
                try { $mail->sendRefund($payment->user); } catch (\Exception $e) {}
                return redirect('/admin/payments')->with('success', "Remboursement de {$amount}€ effectué.");
            } catch (\Exception $e) { $refund->update(['status' => 'failed']); return back()->with('error', "Erreur: {$e->getMessage()}"); }
        }
        return view('admin.refunds.create', compact('payment'));
    }

    // ─── Subscriptions ───
    public function subscriptions() { return view('admin.subscriptions.list', ['subscriptions' => Subscription::with('user', 'plan')->latest()->paginate(25)]); }

    // ─── Tickets ───
    public function tickets(Request $request)
    {
        $q = Ticket::with('user');
        if ($s = $request->input('status')) $q->where('status', $s);
        return view('admin.tickets.list', ['tickets' => $q->latest()->paginate(25), 'statusFilter' => $s]);
    }

    public function ticketDetail(string $id, Request $request)
    {
        $ticket = Ticket::with('user')->findOrFail($id);
        if ($request->isMethod('post')) {
            if ($request->body) TicketMessage::create(['ticket_id' => $ticket->id, 'author_id' => auth()->id(), 'body' => $request->body, 'is_staff_reply' => true]);
            if ($request->status && $request->status !== $ticket->status) { $ticket->update(['status' => $request->status, 'closed_at' => in_array($request->status, ['resolved', 'closed']) ? now() : null]); }
            return back()->with('success', 'Réponse envoyée.');
        }
        return view('admin.tickets.detail', ['ticket' => $ticket, 'messages' => $ticket->messages()->with('author')->get()]);
    }

    // ─── SMTP & Email Templates ───
    public function smtpConfig(Request $request, EmailService $mail)
    {
        $config = SmtpConfiguration::first();
        if ($request->isMethod('post')) {
            if ($request->has('test_email')) {
                if ($config) { $r = $mail->testSmtp($config, $request->test_email_address); return back()->with($r['success'] ? 'success' : 'error', $r['message']); }
                return back()->with('error', 'Sauvegardez d\'abord une configuration.');
            }
            $data = $request->validate(['name' => 'required', 'host' => 'required', 'port' => 'required|integer', 'username' => 'nullable', 'password' => 'nullable', 'use_tls' => 'nullable', 'use_ssl' => 'nullable', 'from_email' => 'required|email', 'from_name' => 'required']);
            $data['use_tls'] = (bool)($data['use_tls'] ?? false);
            $data['use_ssl'] = (bool)($data['use_ssl'] ?? false);
            $config ? $config->update($data) : SmtpConfiguration::create($data);
            return back()->with('success', 'Configuration SMTP sauvegardée.');
        }
        return view('admin.settings.smtp', compact('config'));
    }

    public function emailTemplates() { return view('admin.settings.email-templates', ['templates' => EmailTemplate::all()]); }
    public function emailTemplateEdit(string $id = null, Request $request)
    {
        $tpl = $id ? EmailTemplate::findOrFail($id) : null;
        if ($request->isMethod('post')) {
            $data = $request->validate(['template_type' => 'required', 'subject' => 'required', 'html_body' => 'required', 'is_active' => 'nullable']);
            $data['is_active'] = (bool)($data['is_active'] ?? false);
            $tpl ? $tpl->update($data) : EmailTemplate::create($data);
            return redirect('/admin/settings/email-templates')->with('success', 'Template sauvegardé.');
        }
        return view('admin.settings.email-template-form', ['template' => $tpl]);
    }
}
