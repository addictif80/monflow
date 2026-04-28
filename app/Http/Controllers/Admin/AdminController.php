<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{User, Wallet, WalletTransaction, Subscription, Plan, PromoCode, Payment, Refund, Ticket, TicketMessage, SmtpConfiguration, EmailTemplate, AuditLog, Notification};
use App\Http\Requests\{UserCreateRequest, UserEditRequest, PlanRequest, PromoRequest};
use App\Services\{NavidromeService, StripeService, EmailService};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Hash, DB, Log};

class AdminController extends Controller
{
    // ─── Dashboard ───
    public function dashboard()
    {
        $now = now();
        $lastMonth = now()->subMonth();
        return view('admin.dashboard', [
            'totalUsers' => User::where('is_admin', false)->count(),
            'activeUsers' => User::where('status', 'active')->where('is_admin', false)->count(),
            'suspendedUsers' => User::where('status', 'suspended')->count(),
            'deletedUsers' => User::where('status', 'deleted')->count(),
            'activeSubs' => Subscription::where('status', 'active')->count(),
            'revenueMonth' => Payment::where('status', 'succeeded')->whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->sum('amount'),
            'revenueLastMonth' => Payment::where('status', 'succeeded')->whereMonth('created_at', $lastMonth->month)->whereYear('created_at', $lastMonth->year)->sum('amount'),
            'newUsersMonth' => User::where('is_admin', false)->whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->count(),
            'churnMonth' => Subscription::where('status', 'cancelled')->whereMonth('cancelled_at', $now->month)->whereYear('cancelled_at', $now->year)->count(),
            'expiringSoon' => Subscription::where('status', 'active')->where('current_period_end', '<=', now()->addDays(7))->where('current_period_end', '>', now())->count(),
            'openTickets' => Ticket::whereIn('status', ['open', 'in_progress'])->count(),
            'recentPayments' => Payment::with('user')->latest()->take(10)->get(),
            'recentTickets' => Ticket::with('user')->latest()->take(5)->get(),
            'monthlyRevenue' => Payment::where('status', 'succeeded')
                ->where('created_at', '>=', now()->subMonths(6)->startOfMonth())
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, SUM(amount) as total")
                ->groupBy('month')->orderBy('month')->pluck('total', 'month'),
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

    public function userCreate(UserCreateRequest $request, NavidromeService $nd, EmailService $mail)
    {
        if ($request->isMethod('post')) {
            $data = $request->validated();
            $user = User::create([...$data, 'password' => Hash::make($data['password']), 'is_admin' => (bool)($data['is_admin'] ?? false)]);
            $user->storeEncryptedPassword($data['password']);
            Wallet::create(['user_id' => $user->id]);
            try {
                $r = $nd->createUser($user->username, $data['password'], $user->full_name, $user->email);
                $user->update(['navidrome_id' => $r['id'] ?? null]);
                // Les non-admins doivent souscrire avant d'avoir accès à Navidrome
                if ($user->navidrome_id && !$user->is_admin) {
                    $nd->suspendUser($user->navidrome_id);
                }
            } catch (\Exception $e) { Log::error($e->getMessage()); }
            try { $mail->sendWelcome($user); } catch (\Exception $e) {}
            AuditLog::record('user.create', $user);
            return redirect('/admin/users')->with('success', "Utilisateur {$user->username} créé.");
        }
        return view('admin.users.form', ['title' => 'Nouvel utilisateur', 'user' => null]);
    }

    public function userEdit(string $id, UserEditRequest $request, NavidromeService $nd)
    {
        $user = User::findOrFail($id);
        if ($request->isMethod('post')) {
            $data = $request->validated();
            $plainPassword = $data['password'] ?? null;
            unset($data['password']); // Ne jamais fill le password depuis $data (évite d'écraser avec null)
            $user->fill($data);
            $user->is_admin = (bool)($data['is_admin'] ?? false);
            if (!empty($plainPassword)) {
                $user->password = Hash::make($plainPassword);
                $user->storeEncryptedPassword($plainPassword);
                if ($user->navidrome_id) { try { $nd->changePassword($user->navidrome_id, $plainPassword); } catch (\Exception $e) {} }
            }
            $user->save();
            AuditLog::record('user.edit', $user, ['fields' => array_keys($data)]);
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
        AuditLog::record('user.suspend', $user);
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
        AuditLog::record('user.reactivate', $user);
        return back()->with('success', "Utilisateur {$user->username} réactivé avec son mot de passe original.");
    }

    public function userDelete(string $id, NavidromeService $nd, StripeService $stripe, EmailService $mail)
    {
        $user = User::findOrFail($id);
        try { $mail->sendDeleted($user); } catch (\Exception $e) {}
        if ($user->navidrome_id) {
            try {
                $nd->deleteUser($user->navidrome_id);
                $user->navidrome_id = null; // on nettoie pour éviter toute réutilisation accidentelle
            } catch (\Exception $e) {
                Log::error("Navidrome delete failed for user {$id}: {$e->getMessage()}");
            }
        }
        foreach (Subscription::where('user_id', $id)->whereNotNull('stripe_subscription_id')->where('stripe_subscription_id', '!=', '')->get() as $sub) {
            try { $stripe->cancelSubscriptionNow($sub->stripe_subscription_id); } catch (\Exception $e) {}
        }
        $user->status = 'deleted';
        $user->save();
        AuditLog::record('user.delete', $user);
        return redirect('/admin/users')->with('success', "Utilisateur supprimé.");
    }

    /**
     * Libère l'adresse email d'un compte supprimé pour qu'elle puisse être
     * réutilisée à la création d'un nouveau compte. L'email original est
     * préfixé par "released_<timestamp>_" pour rester unique et traçable.
     */
    public function userReleaseEmail(string $id)
    {
        $user = User::findOrFail($id);
        if ($user->status !== 'deleted') {
            return back()->with('error', 'On ne libère l\'email que pour un compte supprimé.');
        }
        if (str_starts_with($user->email, 'released_')) {
            return back()->with('error', 'L\'email de ce compte a déjà été libéré.');
        }
        $originalEmail = $user->email;
        $user->email = 'released_' . now()->timestamp . '_' . $originalEmail;
        // on libère aussi le username pour la même raison
        $user->username = 'released_' . now()->timestamp . '_' . $user->username;
        $user->save();
        AuditLog::record('user.release_email', $user, ['original_email' => $originalEmail]);
        return back()->with('success', "Email {$originalEmail} libéré — il peut maintenant être réutilisé.");
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
        $user = User::find($userId);
        AuditLog::record('wallet.adjust', $user, ['amount' => $request->amount]);
        return back()->with('success', "Portefeuille ajusté de {$request->amount}€.");
    }

    // ─── Plans ───
    public function plans() { return view('admin.plans.list', ['plans' => Plan::orderBy('sort_order')->get()]); }
    public function planCreate(PlanRequest $request)
    {
        if ($request->isMethod('post')) {
            $plan = Plan::create($request->validated());
            AuditLog::record('plan.create', $plan);
            return redirect('/admin/plans')->with('success', 'Formule créée.');
        }
        return view('admin.plans.form', ['title' => 'Nouvelle formule', 'plan' => null]);
    }
    public function planEdit(string $id, PlanRequest $request)
    {
        $plan = Plan::findOrFail($id);
        if ($request->isMethod('post')) {
            $plan->update($request->validated());
            AuditLog::record('plan.edit', $plan);
            return redirect('/admin/plans')->with('success', 'Formule mise à jour.');
        }
        return view('admin.plans.form', ['title' => "Modifier {$plan->name}", 'plan' => $plan]);
    }

    // ─── Promos ───
    public function promos() { return view('admin.promos.list', ['promos' => PromoCode::latest('created_at')->get()]); }
    public function promoCreate(Request $request)
    {
        if ($request->isMethod('post')) {
            $promo = PromoCode::create($request->validate(['code' => 'required|unique:promo_codes', 'discount_type' => 'required', 'discount_value' => 'required|numeric', 'max_uses' => 'nullable|integer', 'valid_from' => 'required|date', 'valid_until' => 'nullable|date']));
            AuditLog::record('promo.create', $promo);
            return redirect('/admin/promos')->with('success', 'Code promo créé.');
        }
        return view('admin.promos.form', ['title' => 'Nouveau code promo', 'promo' => null]);
    }
    public function promoEdit(string $id, Request $request)
    {
        $promo = PromoCode::findOrFail($id);
        if ($request->isMethod('post')) {
            $promo->update($request->validate(['code' => "required|unique:promo_codes,code,{$id}", 'discount_type' => 'required', 'discount_value' => 'required|numeric', 'max_uses' => 'nullable|integer', 'valid_from' => 'required|date', 'valid_until' => 'nullable|date', 'is_active' => 'nullable']));
            AuditLog::record('promo.edit', $promo);
            return redirect('/admin/promos')->with('success', 'Code mis à jour.');
        }
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
                AuditLog::record('refund.create', $payment, ['amount' => $amount]);
                return redirect('/admin/payments')->with('success', "Remboursement de {$amount}€ effectué.");
            } catch (\Exception $e) { $refund->update(['status' => 'failed']); return back()->with('error', "Erreur: {$e->getMessage()}"); }
        }
        return view('admin.refunds.create', compact('payment'));
    }

    // ─── Subscriptions ───
    public function subscriptions(Request $request)
    {
        $q = Subscription::with('user', 'plan')->latest();
        if ($status = $request->input('status')) $q->where('status', $status);
        return view('admin.subscriptions.list', ['subscriptions' => $q->paginate(25), 'statusFilter' => $status ?? '']);
    }

    public function subscriptionDetail(string $id)
    {
        $sub = Subscription::with('user', 'plan', 'payments')->findOrFail($id);
        return view('admin.subscriptions.detail', ['sub' => $sub, 'plans' => Plan::where('is_active', true)->orderBy('sort_order')->get()]);
    }

    public function subscriptionExtend(string $id, Request $request)
    {
        $sub = Subscription::findOrFail($id);
        $days = (int) $request->input('days', 30);
        if ($days < 1) return back()->with('error', 'Nombre de jours invalide.');

        $base = ($sub->current_period_end && $sub->current_period_end->isFuture()) ? $sub->current_period_end : now();
        $sub->update([
            'current_period_end' => $base->copy()->addDays($days),
            'status' => 'active',
        ]);
        AuditLog::record('subscription.extend', $sub, ['days' => $days]);
        return back()->with('success', "Abonnement prolongé de {$days} jours.");
    }

    public function subscriptionCancel(string $id, StripeService $stripe)
    {
        $sub = Subscription::findOrFail($id);
        if ($sub->stripe_subscription_id) {
            try { $stripe->cancelSubscriptionNow($sub->stripe_subscription_id); } catch (\Exception $e) {}
        }
        $sub->update(['status' => 'cancelled', 'cancelled_at' => now()]);
        AuditLog::record('subscription.cancel', $sub);
        return back()->with('success', 'Abonnement résilié.');
    }

    public function subscriptionChangePlan(string $id, Request $request)
    {
        $sub = Subscription::findOrFail($id);
        $plan = Plan::findOrFail($request->input('plan_id'));
        $sub->update(['plan_id' => $plan->id]);
        AuditLog::record('subscription.change_plan', $sub, ['new_plan' => $plan->name]);
        return back()->with('success', "Formule changée en {$plan->name}.");
    }

    public function subscriptionCreate(Request $request, NavidromeService $nd)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'plan_id' => 'required|exists:plans,id',
            'months' => 'required|integer|min:1|max:24',
        ]);
        $user = User::findOrFail($data['user_id']);
        $plan = Plan::findOrFail($data['plan_id']);
        $days = $plan->period_days * $data['months'];

        if ($user->activeSubscription) return back()->with('error', 'Cet utilisateur a déjà un abonnement actif.');

        $sub = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_start' => now(),
            'current_period_end' => now()->addDays($days),
        ]);

        if ($user->status === 'suspended') $user->update(['status' => 'active']);
        if ($user->navidrome_id) {
            $pw = $user->getDecryptedPassword();
            if ($pw) { try { $nd->reactivateUser($user->navidrome_id, $pw); } catch (\Exception $e) { Log::error($e->getMessage()); } }
        }

        AuditLog::record('subscription.create', $sub, ['plan' => $plan->name, 'months' => $data['months']]);
        return redirect("/admin/subscriptions")->with('success', "Abonnement {$plan->name} créé pour {$user->username} ({$data['months']} mois).");
    }

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

    // ─── Audit Logs ───
    public function auditLogs(Request $request)
    {
        $q = AuditLog::with('admin')->latest();
        if ($action = $request->input('action')) $q->where('action', 'like', "{$action}%");
        return view('admin.audit-logs', ['logs' => $q->paginate(50)]);
    }
}
