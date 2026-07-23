<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{User, Wallet, WalletTransaction, Subscription, Plan, PromoCode, Payment, Refund, Ticket, TicketMessage, SmtpConfiguration, EmailTemplate, AuditLog, Notification, Feedback, Newsletter, AppSetting};
use App\Http\Requests\{UserCreateRequest, UserEditRequest, PlanRequest, PromoRequest};
use App\Services\{NavidromeService, StripeService, EmailService};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Hash, DB, Log, Auth, Artisan};

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

    public function revealPassword(string $id)
    {
        $user = User::findOrFail($id);
        try {
            $password = $user->getDecryptedPassword();
        } catch (\Exception $e) {
            Log::error("Failed to decrypt password for user {$id}: {$e->getMessage()}");
            return response()->json(['success' => false, 'message' => "Mot de passe indisponible (erreur de déchiffrement)."], 422);
        }
        if (!$password) {
            return response()->json(['success' => false, 'message' => "Aucun mot de passe enregistré pour cet utilisateur."], 422);
        }
        AuditLog::record('user.reveal_password', $user);
        return response()->json(['success' => true, 'password' => $password]);
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
        $user->update(['status' => 'active', 'deleted_with_data_kept' => false]);
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

    public function userDelete(string $id, Request $request, NavidromeService $nd, StripeService $stripe, EmailService $mail)
    {
        $user = User::findOrFail($id);
        $keepData = $request->boolean('keep_data');

        if ($keepData) {
            $fee = (float) AppSetting::current()->restoration_fee;
            try { $mail->sendDeletedRecoverable($user, $fee); } catch (\Exception $e) {}
            // Le compte Navidrome et ses données (playlists, historique) sont conservés
            // tels quels pour une éventuelle restauration ultérieure moyennant les frais configurés.
        } else {
            try { $mail->sendDeleted($user); } catch (\Exception $e) {}
            if ($user->navidrome_id) {
                try {
                    $nd->deleteUser($user->navidrome_id);
                    $user->navidrome_id = null; // on nettoie pour éviter toute réutilisation accidentelle
                } catch (\Exception $e) {
                    Log::error("Navidrome delete failed for user {$id}: {$e->getMessage()}");
                }
            }
        }

        foreach (Subscription::where('user_id', $id)->whereNotNull('stripe_subscription_id')->where('stripe_subscription_id', '!=', '')->get() as $sub) {
            try { $stripe->cancelSubscriptionNow($sub->stripe_subscription_id); } catch (\Exception $e) {}
        }
        $user->status = 'deleted';
        $user->deleted_with_data_kept = $keepData;
        $user->save();
        AuditLog::record('user.delete', $user, ['keep_data' => $keepData]);
        return redirect('/admin/users')->with('success', $keepData ? "Utilisateur supprimé (données conservées, mail de récupération envoyé)." : "Utilisateur supprimé.");
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
            $data = $request->validate(['code' => 'required|unique:promo_codes', 'discount_type' => 'required', 'discount_value' => 'required|numeric', 'max_uses' => 'nullable|integer', 'valid_from' => 'required|date', 'valid_until' => 'nullable|date', 'is_recurring' => 'nullable', 'recurring_months' => 'nullable|integer|min:1']);
            $data['is_recurring'] = (bool) ($data['is_recurring'] ?? false);
            if (!$data['is_recurring']) $data['recurring_months'] = null;
            $promo = PromoCode::create($data);
            AuditLog::record('promo.create', $promo);
            return redirect('/admin/promos')->with('success', 'Code promo créé.');
        }
        return view('admin.promos.form', ['title' => 'Nouveau code promo', 'promo' => null]);
    }
    public function promoEdit(string $id, Request $request)
    {
        $promo = PromoCode::findOrFail($id);
        if ($request->isMethod('post')) {
            $data = $request->validate(['code' => "required|unique:promo_codes,code,{$id}", 'discount_type' => 'required', 'discount_value' => 'required|numeric', 'max_uses' => 'nullable|integer', 'valid_from' => 'required|date', 'valid_until' => 'nullable|date', 'is_active' => 'nullable', 'is_recurring' => 'nullable', 'recurring_months' => 'nullable|integer|min:1']);
            $data['is_recurring'] = (bool) ($data['is_recurring'] ?? false);
            if (!$data['is_recurring']) $data['recurring_months'] = null;
            $promo->update($data);
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

    public function subscriptionRemindersEligible()
    {
        // Active, expires within 7 days but not yet past
        $expiring = Subscription::with('user', 'plan')
            ->where('status', 'active')
            ->whereNotNull('current_period_end')
            ->where('current_period_end', '>', now())
            ->where('current_period_end', '<=', now()->addDays(7))
            ->get()
            ->map(fn ($s) => [
                'id'         => $s->id,
                'username'   => $s->user?->username ?? '—',
                'email'      => $s->user?->email    ?? '—',
                'plan'       => $s->plan?->name     ?? '—',
                'price'      => (float) ($s->plan?->price ?? 0),
                'ends_at'    => $s->current_period_end?->format('d/m/Y'),
                'days_left'  => (int) now()->diffInDays($s->current_period_end, true),
            ]);

        // Active but period already past (overdue) OR status pending
        $overdue = Subscription::with('user', 'plan')
            ->where(fn ($q) => $q
                ->where(fn ($q2) => $q2->where('status', 'active')->where('current_period_end', '<', now()))
                ->orWhere('status', 'pending')
            )
            ->get()
            ->map(fn ($s) => [
                'id'           => $s->id,
                'username'     => $s->user?->username ?? '—',
                'email'        => $s->user?->email    ?? '—',
                'plan'         => $s->plan?->name     ?? '—',
                'status'       => $s->status,
                'ends_at'      => $s->current_period_end?->format('d/m/Y') ?? '—',
                'days_overdue' => $s->is_overdue ? $s->days_overdue : 0,
            ]);

        return response()->json(['expiring' => $expiring->values(), 'overdue' => $overdue->values()]);
    }

    public function subscriptionSendReminder(string $id, Request $request, EmailService $email)
    {
        $type = $request->input('type'); // 'renewal' | 'payment'
        $sub  = Subscription::with('user', 'plan')->findOrFail($id);
        $user = $sub->user;

        if (!$user) {
            return response()->json(['error' => 'Utilisateur introuvable.'], 422);
        }

        if ($type === 'renewal') {
            $email->sendRenewalReminder($user, $sub->plan, (float) ($sub->plan?->price ?? 0));
        } else {
            $email->sendPaymentReminder($user, $sub->days_overdue);
        }

        AuditLog::record('subscription.reminder_sent', $sub, ['type' => $type, 'to' => $user->email]);
        return response()->json(['success' => true, 'email' => $user->email]);
    }

    public function subscriptionPreviewOverdue(Request $request)
    {
        $keepData = $request->boolean('keep_data');
        $args = ['--dry-run' => true];
        if ($keepData) $args['--keep-data'] = true;
        Artisan::call('subscriptions:check-overdue', $args);
        $output = trim(Artisan::output());
        return response()->json(['success' => true, 'output' => $output]);
    }

    public function subscriptionProcessOverdue(Request $request)
    {
        $keepData = $request->boolean('keep_data');
        Artisan::call('subscriptions:check-overdue', $keepData ? ['--keep-data' => true] : []);
        $output = trim(Artisan::output());
        AuditLog::record('subscription.process_overdue', null, ['output' => $output, 'keep_data' => $keepData]);
        return response()->json(['success' => true, 'output' => $output]);
    }

    public function subscriptionProcessReminders()
    {
        Artisan::call('subscriptions:send-payment-reminders');
        $paymentOutput = trim(Artisan::output());
        Artisan::call('subscriptions:send-renewal-reminders');
        $renewalOutput = trim(Artisan::output());
        $output = "Rappels de paiement :\n{$paymentOutput}\n\nRappels de renouvellement :\n{$renewalOutput}";
        AuditLog::record('subscription.process_reminders', null, ['output' => $output]);
        return response()->json(['success' => true, 'output' => $output]);
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

    public function subscriptionUpdateDates(string $id, Request $request)
    {
        $data = $request->validate([
            'current_period_start' => 'required|date',
            'current_period_end' => 'required|date|after:current_period_start',
        ]);
        $sub = Subscription::findOrFail($id);
        $old = [
            'start' => $sub->current_period_start?->toDateTimeString(),
            'end' => $sub->current_period_end?->toDateTimeString(),
        ];
        $sub->update([
            'current_period_start' => $data['current_period_start'],
            'current_period_end' => $data['current_period_end'],
        ]);
        AuditLog::record('subscription.update_dates', $sub, ['old' => $old, 'new' => $data]);
        return back()->with('success', 'Dates mises à jour.');
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

    public function restorationFeeSettings(Request $request)
    {
        $settings = AppSetting::current();
        if ($request->isMethod('post')) {
            $data = $request->validate(['restoration_fee' => 'required|numeric|min:0']);
            $settings->update($data);
            return back()->with('success', 'Frais de restauration mis à jour.');
        }
        return view('admin.settings.restoration-fee', compact('settings'));
    }

    public function emailTemplates() { return view('admin.settings.email-templates', ['templates' => EmailTemplate::all()]); }
    public function emailTemplateEdit(Request $request, ?string $id = null)
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

    // ─── Lyrics Management ───
    public function lyrics(Request $request, NavidromeService $nd)
    {
        $q       = (string) $request->input('q', '');
        $page    = max(1, (int) $request->input('page', 1));
        $perPage = 100;
        $start   = ($page - 1) * $perPage;
        $songs   = [];
        $total   = 0;

        try {
            $result = $nd->getAllSongsPaginated($start, $perPage, 'title', 'ASC', $q);
            $songs  = $result['data'];
            $total  = $result['total'];
        } catch (\Exception $e) {}

        // Build host LRC paths and batch-check existence
        $musicHostPath      = config('navidrome.music_host_path');
        $containerMusicPath = rtrim(config('navidrome.container_music_path', '/music'), '/');
        $lrcPathMap = [];
        foreach ($songs as $song) {
            $path = $song['path'] ?? null;
            if (!$path) continue;
            if ($musicHostPath && str_starts_with($path, $containerMusicPath . '/')) {
                $hostPath = rtrim($musicHostPath, '/') . substr($path, strlen($containerMusicPath));
            } elseif (str_starts_with($path, '/')) {
                $hostPath = $path;
            } else {
                $hostPath = rtrim(config('navidrome.music_path', ''), '/') . '/' . ltrim($path, '/');
            }
            $lrcPathMap[$song['id']] = preg_replace('/\.[^.]+$/', '.lrc', $hostPath);
        }

        $existingLrc = [];
        if (!empty($lrcPathMap)) {
            try { $existingLrc = $nd->batchCheckLrc(array_values($lrcPathMap)); } catch (\Exception $e) {}
        }

        foreach ($songs as &$song) {
            $lrcPath = $lrcPathMap[$song['id']] ?? null;
            $song['hasLyrics'] = $lrcPath !== null && in_array($lrcPath, $existingLrc);
        }
        unset($song);

        $lastPage = $total > 0 ? (int) ceil($total / $perPage) : 1;
        return view('admin.lyrics.index', compact('songs', 'q', 'page', 'perPage', 'total', 'lastPage'));
    }

    public function lyricsGet(string $id, NavidromeService $nd)
    {
        $lrc = $nd->getLyricsBySongId($id) ?? '';
        return response()->json(['lrc' => $lrc]);
    }

    public function lyricsSave(string $id, Request $request, NavidromeService $nd)
    {
        $song       = $nd->getSong($id);
        $lrcContent = $request->input('lrc_content', '');
        $songPath   = $song['path'] ?? null;

        if (!$songPath) {
            $err = 'Chemin du fichier audio introuvable.';
            return $request->expectsJson() ? response()->json(['error' => $err], 422) : back()->with('error', $err);
        }

        // Translate container path to host path
        $musicHostPath      = config('navidrome.music_host_path');
        $containerMusicPath = rtrim(config('navidrome.container_music_path', '/music'), '/');
        if ($musicHostPath && str_starts_with($songPath, $containerMusicPath . '/')) {
            $fullPath = rtrim($musicHostPath, '/') . substr($songPath, strlen($containerMusicPath));
        } elseif (str_starts_with($songPath, '/')) {
            $fullPath = $songPath;
        } else {
            $fullPath = rtrim(config('navidrome.music_path', ''), '/') . '/' . ltrim($songPath, '/');
        }

        $lrcPath = preg_replace('/\.[^.]+$/', '.lrc', $fullPath);

        try {
            $result = $nd->writeLrcViaSSH($lrcPath, $lrcContent);
            if ($result['exitCode'] !== 0) {
                $err = 'Erreur écriture LRC : ' . $result['output'];
                return $request->expectsJson() ? response()->json(['error' => $err], 422) : back()->with('error', $err);
            }
            $nd->triggerScan();
        } catch (\RuntimeException $e) {
            $dir = dirname($lrcPath);
            if (!is_dir($dir)) @mkdir($dir, 0755, true);
            file_put_contents($lrcPath, $lrcContent);
        }

        AuditLog::record('lyrics.save', null, ['song_id' => $id, 'title' => $song['title'] ?? '']);
        return $request->expectsJson()
            ? response()->json(['success' => true])
            : back()->with('success', 'Paroles enregistrées. Un scan Navidrome a été lancé.');
    }

    public function lyricsDownload(string $id, NavidromeService $nd)
    {
        $song = $nd->getSong($id);
        $params = ['track_name' => $song['title'] ?? '', 'artist_name' => $song['artist'] ?? ''];
        if (!empty($song['album']))    $params['album_name'] = $song['album'];
        if (!empty($song['duration'])) $params['duration']   = (int) $song['duration'];

        try {
            $res = \Illuminate\Support\Facades\Http::timeout(10)
                ->get('https://lrclib.net/api/get', $params);
            if ($res->status() === 404) {
                return response()->json(['error' => 'Paroles introuvables sur LRCLIB.'], 404);
            }
            $res->throw();
            $data = $res->json();
            $lrc  = $data['syncedLyrics'] ?? $data['plainLyrics'] ?? null;
            if (!$lrc) {
                return response()->json(['error' => 'Aucune parole disponible sur LRCLIB.'], 404);
            }
            return response()->json(['lrc' => $lrc, 'synced' => !empty($data['syncedLyrics'])]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function lyricsMissing(NavidromeService $nd)
    {
        $missing            = [];
        $start              = 0;
        $perPage            = 2000;
        $musicHostPath      = config('navidrome.music_host_path');
        $containerMusicPath = rtrim(config('navidrome.container_music_path', '/music'), '/');

        do {
            try {
                $result = $nd->getAllSongsPaginated($start, $perPage, 'title', 'ASC');
                $page   = $result['data'];
                $total  = $result['total'];
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }

            $lrcPathMap = [];
            foreach ($page as $song) {
                $path = $song['path'] ?? null;
                if (!$path) continue;
                if ($musicHostPath && str_starts_with($path, $containerMusicPath . '/')) {
                    $hostPath = rtrim($musicHostPath, '/') . substr($path, strlen($containerMusicPath));
                } elseif (str_starts_with($path, '/')) {
                    $hostPath = $path;
                } else {
                    $hostPath = rtrim(config('navidrome.music_path', ''), '/') . '/' . ltrim($path, '/');
                }
                $lrcPathMap[$song['id']] = preg_replace('/\.[^.]+$/', '.lrc', $hostPath);
            }

            $existingLrc = [];
            if (!empty($lrcPathMap)) {
                try { $existingLrc = $nd->batchCheckLrc(array_values($lrcPathMap)); } catch (\Exception $e) {}
            }

            foreach ($page as $song) {
                $lrcPath = $lrcPathMap[$song['id']] ?? null;
                if (!$lrcPath || !in_array($lrcPath, $existingLrc)) {
                    $missing[] = ['id' => $song['id'], 'title' => $song['title'] ?? '', 'artist' => $song['artist'] ?? '', 'album' => $song['album'] ?? ''];
                }
            }

            $start += $perPage;
            if (count($page) === $perPage && $start < $total) {
                usleep(300000); // 300 ms between pages to avoid rate limiting
            }
        } while (count($page) === $perPage && $start < $total);

        return response()->json($missing);
    }

    public function lyricsEdit(string $id, NavidromeService $nd)
    {
        $song = $nd->getSong($id);
        $lrcContent = $nd->getLyricsBySongId($id) ?? '';
        return view('admin.lyrics.edit', compact('song', 'lrcContent'));
    }

    public function lyricsStream(string $id, NavidromeService $nd)
    {
        try {
            $response = $nd->streamSong($id);
            return response($response->body(), $response->status())
                ->header('Content-Type', $response->header('Content-Type') ?? 'audio/mpeg')
                ->header('Accept-Ranges', 'bytes');
        } catch (\Exception $e) {
            return response('Fichier introuvable', 404);
        }
    }

    // ─── Metadata Management ───
    public function metadata(Request $request, NavidromeService $nd)
    {
        $q       = (string) $request->input('q', '');
        $page    = max(1, (int) $request->input('page', 1));
        $perPage = 100;
        $start   = ($page - 1) * $perPage;
        $songs   = [];
        $total   = 0;

        try {
            $result = $nd->getAllSongsPaginated($start, $perPage, 'title', 'ASC', $q);
            $songs  = $result['data'];
            $total  = $result['total'];
        } catch (\Exception $e) {}

        $lastPage = $total > 0 ? (int) ceil($total / $perPage) : 1;

        return view('admin.metadata.index', compact('songs', 'q', 'page', 'perPage', 'total', 'lastPage'));
    }

    public function metadataEdit(string $id, NavidromeService $nd)
    {
        $song = $nd->getSong($id);
        $albumSongs = [];
        if ($albumId = $song['albumId'] ?? null) {
            try { $albumSongs = $nd->getAlbumSongs($albumId); } catch (\Exception $e) {}
        }
        return view('admin.metadata.edit', compact('song', 'albumSongs'));
    }

    public function metadataSave(string $id, Request $request, NavidromeService $nd)
    {
        $data = $request->validate([
            'title' => 'required|max:255',
            'artist' => 'nullable|max:255',
            'albumArtist' => 'nullable|max:255',
            'album' => 'nullable|max:255',
            'genre' => 'nullable|max:100',
            'trackNumber' => 'nullable|integer|min:0',
            'discNumber' => 'nullable|integer|min:0',
            'year' => 'nullable|integer|min:0|max:9999',
            'comment' => 'nullable|max:1000',
        ]);

        $song = $nd->getSong($id);
        $songPath = $song['path'] ?? null;
        if (!$songPath) {
            $err = 'Chemin du fichier audio introuvable.';
            return $request->expectsJson() ? response()->json(['error' => $err], 422) : back()->with('error', $err);
        }

        // Translate container path to host path (same logic as duplicate delete).
        $musicHostPath      = config('navidrome.music_host_path');
        $containerMusicPath = rtrim(config('navidrome.container_music_path', '/music'), '/');
        if ($musicHostPath && str_starts_with($songPath, $containerMusicPath . '/')) {
            $fullPath = rtrim($musicHostPath, '/') . substr($songPath, strlen($containerMusicPath));
        } elseif (str_starts_with($songPath, '/')) {
            $fullPath = $songPath;
        } else {
            $fullPath = rtrim(config('navidrome.music_path', ''), '/') . '/' . ltrim($songPath, '/');
        }

        $tagMap = [
            'title' => 'title', 'artist' => 'artist', 'albumArtist' => 'album_artist',
            'album' => 'album', 'genre' => 'genre', 'year' => 'date',
            'trackNumber' => 'track', 'discNumber' => 'disc', 'comment' => 'comment',
        ];

        $metaArgs = '';
        foreach ($tagMap as $formField => $ffmpegTag) {
            $val = $data[$formField] ?? '';
            if ($val !== '' && $val !== null) {
                $metaArgs .= ' -metadata ' . escapeshellarg("{$ffmpegTag}={$val}");
            }
        }

        if (!$metaArgs) {
            $err = 'Aucune modification.';
            return $request->expectsJson() ? response()->json(['error' => $err], 422) : back()->with('error', $err);
        }

        $ext     = pathinfo($fullPath, PATHINFO_EXTENSION);
        $tmpFile = preg_replace('/\.[^.]+$/', '.__tmp__.' . $ext, $fullPath);
        $escaped = escapeshellarg($fullPath);
        $tmpPath = escapeshellarg($tmpFile);
        // sh -c wrapper ensures sudo covers ffmpeg + mv as a unit
        $cmd = 'sh -c ' . escapeshellarg("ffmpeg -i {$escaped} -c copy{$metaArgs} -y {$tmpPath} && mv -f {$tmpPath} {$escaped}");

        try {
            $result = $nd->sshCommand($cmd);
            if ($result['exitCode'] !== 0) {
                $err = 'Erreur ffmpeg : ' . $result['output'];
                return $request->expectsJson()
                    ? response()->json(['error' => $err], 422)
                    : back()->with('error', $err);
            }
            $nd->triggerScan();
            AuditLog::record('metadata.update', null, ['song_id' => $id, 'fields' => array_keys(array_filter($data, fn ($v) => $v !== null && $v !== ''))]);
            return $request->expectsJson()
                ? response()->json(['success' => true])
                : back()->with('success', 'Metadonnees mises a jour. Scan Navidrome lance.');
        } catch (\Exception $e) {
            $err = 'Erreur : ' . $e->getMessage();
            return $request->expectsJson()
                ? response()->json(['error' => $err], 500)
                : back()->with('error', $err);
        }
    }

    public function metadataCoverArt(string $id, NavidromeService $nd)
    {
        try {
            $stream = $nd->getCoverArt($id, 120);
            return response()->stream(function () use ($stream) {
                echo $stream->body();
            }, 200, [
                'Content-Type'  => 'image/jpeg',
                'Cache-Control' => 'private, max-age=300',
            ]);
        } catch (\Exception) {
            abort(404);
        }
    }

    public function metadataMissingCovers(NavidromeService $nd)
    {
        $missing = [];
        $start   = 0;
        $perPage = 500;

        do {
            try {
                $result = $nd->getAllSongsPaginated($start, $perPage, 'title', 'ASC');
                $page   = $result['data'];
                $total  = $result['total'];
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }

            foreach ($page as $song) {
                // coverArt absent or empty means no embedded/folder art
                if (empty($song['coverArt'])) {
                    $missing[] = [
                        'id'     => $song['id'],
                        'title'  => $song['title']  ?? '',
                        'artist' => $song['artist']  ?? '',
                        'album'  => $song['album']   ?? '',
                    ];
                }
            }

            $start += $perPage;
        } while (count($page) === $perPage && $start < $total);

        return response()->json($missing);
    }

    public function metadataSearchArtwork(Request $request)
    {
        $q = trim($request->input('q', ''));
        if (!$q) return response()->json([]);

        try {
            $res = \Illuminate\Support\Facades\Http::timeout(8)
                ->get('https://itunes.apple.com/search', [
                    'term'   => $q,
                    'entity' => 'album',
                    'limit'  => 8,
                ]);
            $results = collect($res->json('results') ?? [])
                ->filter(fn ($r) => isset($r['artworkUrl100']))
                ->map(fn ($r) => [
                    'thumb' => $r['artworkUrl100'],
                    'full'  => str_replace('100x100bb', '600x600bb', $r['artworkUrl100']),
                    'label' => ($r['artistName'] ?? '') . ' — ' . ($r['collectionName'] ?? ''),
                ])
                ->values();
            return response()->json($results);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function metadataCover(string $id, Request $request, NavidromeService $nd)
    {
        $data = $request->validate(['artwork_url' => 'required|url|max:500']);

        $song     = $nd->getSong($id);
        $songPath = $song['path'] ?? null;
        if (!$songPath) {
            return response()->json(['error' => 'Fichier introuvable.'], 422);
        }

        // Translate container path to host path
        $musicHostPath      = config('navidrome.music_host_path');
        $containerMusicPath = rtrim(config('navidrome.container_music_path', '/music'), '/');
        if ($musicHostPath && str_starts_with($songPath, $containerMusicPath . '/')) {
            $fullPath = rtrim($musicHostPath, '/') . substr($songPath, strlen($containerMusicPath));
        } elseif (str_starts_with($songPath, '/')) {
            $fullPath = $songPath;
        } else {
            $fullPath = rtrim(config('navidrome.music_path', ''), '/') . '/' . ltrim($songPath, '/');
        }

        try {
            $tmpCover = '/tmp/mf_cover_' . $id . '.jpg';
            $eCover   = escapeshellarg($tmpCover);
            $eUrl     = escapeshellarg($data['artwork_url']);

            // Let the Synology download the artwork directly — avoids SCP entirely.
            // curl is available on Synology DSM; wget is the fallback.
            $download = $nd->sshCommand("curl -sS -L --max-time 20 -o {$eCover} {$eUrl}");
            if ($download['exitCode'] !== 0) {
                $download = $nd->sshCommand("wget -q -O {$eCover} {$eUrl}");
                if ($download['exitCode'] !== 0) {
                    return response()->json(['error' => 'Impossible de télécharger la pochette sur le Synology : ' . $download['output']], 422);
                }
            }

            // Build ffmpeg command to embed cover art (works for MP3 and FLAC).
            // Wrapped in sh -c so that sudo applies to ffmpeg + mv + rm as a unit —
            // without this, only ffmpeg runs as root; mv/rm fail on root-owned files.
            $ext     = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
            $tmpSong = preg_replace('/\.[^.]+$/', '.__tmp__.' . $ext, $fullPath);
            $eSong   = escapeshellarg($fullPath);
            $eTmp    = escapeshellarg($tmpSong);

            $inner = "ffmpeg -i {$eSong} -i {$eCover} -map 0:a -map 1:v -c:a copy -c:v copy"
                   . " -id3v2_version 3 -disposition:v:0 attached_pic"
                   . " -y {$eTmp} && mv -f {$eTmp} {$eSong} && rm -f {$eCover}";
            $cmd = 'sh -c ' . escapeshellarg($inner);

            $result = $nd->sshCommand($cmd);
            if ($result['exitCode'] !== 0) {
                $nd->sshCommand('sh -c ' . escapeshellarg("rm -f {$eCover}"));
                return response()->json(['error' => 'Erreur ffmpeg : ' . $result['output']], 422);
            }

            $nd->triggerScan();
            AuditLog::record('metadata.cover', null, ['song_id' => $id]);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ─── Duplicate Management ───
    public function duplicates(Request $request, NavidromeService $nd)
    {
        $duplicates = [];
        $scanned = false;

        if ($request->has('scan')) {
            $scanned = true;
            try {
                $allSongs = $nd->getAllSongs(0, 10000, 'title', 'ASC');
                $grouped = [];
                foreach ($allSongs as $song) {
                    $key = mb_strtolower(trim($song['title'] ?? '')) . '|||' . mb_strtolower(trim($song['artist'] ?? ''));
                    $grouped[$key][] = $song;
                }
                foreach ($grouped as $group) {
                    if (count($group) > 1) {
                        $duplicates[] = $group;
                    }
                }
                usort($duplicates, fn ($a, $b) => strcasecmp($a[0]['title'] ?? '', $b[0]['title'] ?? ''));
            } catch (\Exception $e) {
                return back()->with('error', 'Erreur lors du scan : ' . $e->getMessage());
            }
        }

        return view('admin.duplicates.index', compact('duplicates', 'scanned'));
    }

    public function duplicateScanStatus(NavidromeService $nd)
    {
        return response()->json($nd->getScanStatus());
    }

    public function duplicateBatchDelete(Request $request, NavidromeService $nd)
    {
        $ids    = $request->input('ids', []);
        $paths  = $request->input('paths', []);
        $titles = $request->input('titles', []);

        if (empty($ids)) {
            return back()->with('error', 'Aucun fichier sélectionné.');
        }

        // paths[] and ids[] are submitted together from the view — no need to
        // re-fetch each song individually from Navidrome (avoids N×HTTP timeouts).
        $musicPath = config('navidrome.music_path');
        $fullPaths = [];
        $errors    = [];

        foreach ($ids as $i => $id) {
            $songPath = $paths[$i] ?? null;
            if (!$songPath) {
                continue; // stream or remote track — no local file to delete
            }
            // Navidrome's internal API returns full absolute paths already.
            // Only prepend music_path when the stored path is relative.
            $fullPath = str_starts_with($songPath, '/') ? $songPath : ($musicPath . '/' . ltrim($songPath, '/'));
            $fullPaths[$id] = [
                'path'  => $fullPath,
                'title' => $titles[$i] ?? '',
            ];
        }

        $deleted = 0;
        if (!empty($fullPaths)) {
            try {
                $pathList = implode("\n", array_column($fullPaths, 'path'));
                $encoded  = base64_encode($pathList);

                // 1. Supprimer les fichiers physiques.
                //
                // Strategy A — music_host_path configured:
                //   Navidrome is in Docker. Paths in the DB are container-internal
                //   (e.g. /music/Artist/file.flac). We map them to host paths by
                //   replacing the container prefix with NAVIDROME_MUSIC_HOST_PATH,
                //   then run rm directly on the SSH host. No docker exec needed.
                //
                // Strategy B — docker_container configured, no music_host_path:
                //   Run rm inside the container via docker exec (legacy behaviour).
                //
                // Strategy C — neither:
                //   Run rm directly on the SSH host with the paths as-is.
                //
                // The script echoes DELETED:/MISSING:/FAILED: per file.

                $musicHostPath      = config('navidrome.music_host_path');
                $containerMusicPath = rtrim(config('navidrome.container_music_path', '/music'), '/');
                $container          = config('navidrome.docker_container');

                if ($musicHostPath) {
                    // Strategy A: translate container paths to host paths
                    $musicHostPath = rtrim($musicHostPath, '/');
                    $translatedList = implode("\n", array_map(
                        fn($p) => str_starts_with($p, $containerMusicPath . '/')
                            ? $musicHostPath . substr($p, strlen($containerMusicPath))
                            : $p,
                        array_column($fullPaths, 'path')
                    ));
                    $encodedHost  = base64_encode($translatedList);
                    $innerScript  = 'echo ' . $encodedHost . ' | base64 -d | '
                        . 'while IFS= read -r f; do '
                        .   'if [ -f "$f" ]; then rm -f "$f" && echo "DELETED:$f" || echo "FAILED:$f"; '
                        .   'else echo "MISSING:$f"; fi; '
                        . 'done';
                    $script = $innerScript;
                } elseif ($container) {
                    // Strategy B: delete inside Docker container
                    $innerScript = 'echo ' . $encoded . ' | base64 -d | '
                        . 'while IFS= read -r f; do '
                        .   'if [ -f "$f" ]; then rm -f "$f" && echo "DELETED:$f" || echo "FAILED:$f"; '
                        .   'else echo "MISSING:$f"; fi; '
                        . 'done';
                    $script = 'docker exec ' . escapeshellarg($container) . ' sh -c ' . escapeshellarg($innerScript);
                } else {
                    // Strategy C: direct SSH host deletion
                    $innerScript = 'echo ' . $encoded . ' | base64 -d | '
                        . 'while IFS= read -r f; do '
                        .   'if [ -f "$f" ]; then rm -f "$f" && echo "DELETED:$f" || echo "FAILED:$f"; '
                        .   'else echo "MISSING:$f"; fi; '
                        . 'done';
                    $script = $innerScript;
                }

                $result = $nd->sshCommand($script);

                // When using Strategy A (host path), DELETED/MISSING markers use host
                // paths. Re-map them back to container paths so DB lookup still works.
                $hostToContainer = [];
                if ($musicHostPath) {
                    foreach ($fullPaths as $id => $info) {
                        $containerPath = $info['path'];
                        $hostPath = str_starts_with($containerPath, $containerMusicPath . '/')
                            ? $musicHostPath . substr($containerPath, strlen($containerMusicPath))
                            : $containerPath;
                        $hostToContainer[$hostPath] = $containerPath;
                    }
                }

                // Parser la sortie ligne par ligne.
                // Si Strategy A, les chemins dans la sortie sont des chemins HOST.
                // On les retraduit en chemins conteneur pour matcher $fullPaths (indexé par chemin conteneur).
                $actualDeleted = [];
                $missing       = [];
                $failed        = [];
                $remap = fn($p) => $hostToContainer[$p] ?? $p;
                foreach (explode("\n", trim($result['output'] ?? '')) as $line) {
                    $line = trim($line);
                    if (str_starts_with($line, 'DELETED:')) $actualDeleted[] = $remap(substr($line, 8));
                    elseif (str_starts_with($line, 'MISSING:')) $missing[]   = $remap(substr($line, 8));
                    elseif (str_starts_with($line, 'FAILED:'))  $failed[]    = $remap(substr($line, 7));
                }

                if ($result['exitCode'] === 0) {
                    $deleted = count($actualDeleted);

                    // N'audit que les fichiers réellement supprimés
                    $pathToId = [];
                    foreach ($fullPaths as $id => $info) {
                        $pathToId[$info['path']] = ['id' => $id, 'title' => $info['title']];
                    }
                    foreach ($actualDeleted as $path) {
                        $meta = $pathToId[$path] ?? null;
                        AuditLog::record('duplicate.delete', null, [
                            'song_id' => $meta['id'] ?? null,
                            'title'   => $meta['title'] ?? basename($path),
                            'path'    => $path,
                        ]);
                    }
                    if (!empty($missing)) {
                        $hint = $musicHostPath ? '' : ' — Configurez NAVIDROME_MUSIC_HOST_PATH dans .env si Navidrome est dans Docker';
                        $errors[] = count($missing) . ' fichier(s) introuvable(s) sur le serveur (chemin : ' . (array_column($fullPaths, 'path')[0] ?? '?') . ')' . $hint;
                    }
                    if (!empty($failed)) {
                        $errors[] = count($failed) . ' suppression(s) échouée(s) (droits ?)';
                    }

                    // 2. Supprimer de la BDD les entrées dont le fichier n'est plus sur disque
                    //    (supprimés avec succès + déjà absents = ghost entries).
                    //    Les fichiers en échec (droits) restent sur disque → on les garde en BDD.
                    $goneFromDisk = array_merge($actualDeleted, $missing);
                    $idsToRemove  = [];
                    foreach ($goneFromDisk as $path) {
                        if (isset($pathToId[$path])) {
                            $idsToRemove[] = $pathToId[$path]['id'];
                        }
                    }

                    if (!empty($idsToRemove)) {
                        $safeIds = array_map(fn($id) => str_replace("'", "''", $id), $idsToRemove);
                        $inList  = "'" . implode("','", $safeIds) . "'";
                        $sqlStmt = "DELETE FROM media_file WHERE id IN ({$inList});";

                        $container = config('navidrome.docker_container');
                        $dbPath    = config('navidrome.db_path');

                        if ($container) {
                            $nd->sshCommand(
                                'docker exec ' . escapeshellarg($container) .
                                ' sqlite3 ' . escapeshellarg($dbPath) .
                                ' ' . escapeshellarg($sqlStmt)
                            );
                        } elseif ($dbPath) {
                            $nd->sshCommand(
                                'sqlite3 ' . escapeshellarg($dbPath) . ' ' . escapeshellarg($sqlStmt)
                            );
                        }
                    }

                    // Scan léger pour recalculer les compteurs album/artiste
                    $nd->triggerScan(false);
                } else {
                    $errors[] = $result['output'];
                }
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        $total = count($fullPaths);
        if ($deleted > 0) {
            $msg = "{$deleted}/{$total} fichier(s) supprimé(s) du serveur et retirés de la bibliothèque Navidrome.";
        } elseif ($total > 0) {
            $msg = '';
        } else {
            $msg = '';
        }
        if (!empty($errors)) {
            $msg .= ($msg ? ' ' : '') . 'Erreurs : ' . implode(', ', $errors);
        }

        $redirect = redirect('/admin/duplicates');
        if ($deleted > 0) {
            $redirect = $redirect->with('success', $msg);
        } else {
            $redirect = $redirect->with('error', $msg ?: 'Aucun fichier à supprimer.');
        }
        return $redirect;
    }

    // ─── Email Logs ───
    public function emailLogs(Request $request)
    {
        $q      = (string) $request->input('q', '');
        $status = (string) $request->input('status', '');
        $type   = (string) $request->input('type', '');

        $query = \App\Models\EmailLog::latest();
        if ($q)      $query->where(fn ($b) => $b->where('to', 'like', "%{$q}%")->orWhere('subject', 'like', "%{$q}%"));
        if ($status) $query->where('status', $status);
        if ($type)   $query->where('template_type', $type);

        $logs  = $query->paginate(50)->withQueryString();
        $types = \App\Models\EmailLog::distinct()->orderBy('template_type')->pluck('template_type')->filter()->values();

        return view('admin.email-logs.index', compact('logs', 'q', 'status', 'type', 'types'));
    }

    public function emailLogPreview(string $id)
    {
        $log = \App\Models\EmailLog::findOrFail($id);
        return response($log->html_body, 200)->header('Content-Type', 'text/html; charset=utf-8');
    }

    // ─── Audit Logs ───
    public function auditLogs(Request $request)
    {
        $q = AuditLog::with('admin')->latest();
        if ($action = $request->input('action')) $q->where('action', 'like', "{$action}%");
        return view('admin.audit-logs', ['logs' => $q->paginate(50)]);
    }

    // ─── Server Logs ───
    public function serverLogs(Request $request)
    {
        $logFile = storage_path('logs/laravel.log');
        $lines   = (int) $request->input('lines', 200);
        $lines   = max(50, min(2000, $lines));
        $filter  = $request->input('filter', '');

        $entries = [];
        if (file_exists($logFile)) {
            // Lire les N dernières lignes efficacement sans charger tout le fichier
            $content = $this->tailFile($logFile, $lines * 10); // sur-lire pour filtrer
            $raw = explode("\n", $content);

            $current = null;
            foreach ($raw as $line) {
                // Nouvelle entrée de log : commence par [YYYY-MM-DD
                if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (\w+)\.(\w+): (.+)/', $line, $m)) {
                    if ($current !== null) $entries[] = $current;
                    $current = [
                        'datetime' => $m[1],
                        'env'      => $m[2],
                        'level'    => strtolower($m[3]),
                        'message'  => $m[4],
                        'context'  => '',
                        'raw'      => $line,
                    ];
                } elseif ($current !== null) {
                    $current['context'] .= "\n" . $line;
                    $current['raw']     .= "\n" . $line;
                }
            }
            if ($current !== null) $entries[] = $current;

            // Appliquer filtre texte
            if ($filter !== '') {
                $entries = array_filter($entries, fn($e) =>
                    stripos($e['raw'], $filter) !== false
                );
            }

            // Garder les N dernières entrées
            $entries = array_slice(array_values($entries), -$lines);
            $entries = array_reverse($entries); // plus récent en premier
        }

        return view('admin.logs', compact('entries', 'lines', 'filter', 'logFile'));
    }

    private function tailFile(string $path, int $maxLines): string
    {
        $fp   = fopen($path, 'rb');
        $size = filesize($path);
        if (!$fp || $size === 0) return '';

        $chunk  = 65536; // 64 KB
        $buffer = '';
        $pos    = $size;
        $found  = 0;

        while ($pos > 0 && $found < $maxLines) {
            $read   = min($chunk, $pos);
            $pos   -= $read;
            fseek($fp, $pos);
            $buffer = fread($fp, $read) . $buffer;
            $found  = substr_count($buffer, "\n");
        }
        fclose($fp);
        return $buffer;
    }

    // ─── Feedbacks ───
    public function feedbacks(Request $request)
    {
        $q = Feedback::with('user', 'ticket')->latest();
        if ($status = $request->input('status')) $q->where('status', $status);
        if ($type = $request->input('type')) $q->where('type', $type);
        return view('admin.feedbacks.list', ['feedbacks' => $q->paginate(25), 'statusFilter' => $status ?? '', 'typeFilter' => $type ?? '']);
    }

    public function feedbackDetail(string $id, Request $request)
    {
        $feedback = Feedback::with('user', 'ticket')->findOrFail($id);
        if ($request->isMethod('post')) {
            $data = $request->validate([
                'status' => 'required|in:new,reviewed,in_progress,resolved,dismissed',
                'admin_note' => 'nullable|max:2000',
            ]);
            $feedback->update($data);
            AuditLog::record('feedback.update', $feedback, ['status' => $data['status']]);
            return back()->with('success', 'Feedback mis à jour.');
        }
        return view('admin.feedbacks.detail', ['feedback' => $feedback]);
    }

    public function feedbackToTicket(string $id)
    {
        $feedback = Feedback::with('user')->findOrFail($id);
        if ($feedback->ticket_id) return back()->with('error', 'Un ticket est déjà lié à ce feedback.');

        $ticket = Ticket::create([
            'user_id' => $feedback->user_id,
            'subject' => "[Feedback] {$feedback->subject}",
            'category' => $feedback->type === 'bug' ? 'technical' : 'other',
            'priority' => 'medium',
        ]);
        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'author_id' => $feedback->user_id,
            'body' => $feedback->body,
        ]);
        $feedback->update(['ticket_id' => $ticket->id, 'status' => 'in_progress']);

        AuditLog::record('feedback.to_ticket', $feedback, ['ticket_id' => $ticket->id]);
        Notification::send($feedback->user_id, 'support', 'Feedback pris en charge', "Votre feedback \"{$feedback->subject}\" a été transmis au support.", "/support/tickets/{$ticket->id}");

        return redirect("/admin/tickets/{$ticket->id}")->with('success', 'Ticket créé à partir du feedback.');
    }

    // ─── Newsletters ───
    // ─── Newsletter helpers ───

    private function getNewsletterLayout(): string
    {
        return EmailTemplate::where('template_type', 'newsletter_layout')->value('html_body') ?? '{{ content }}';
    }

    private function applyNewsletterLayout(string $content): string
    {
        $layout = $this->getNewsletterLayout();
        return str_replace('{{ content }}', $content, $layout);
    }

    // ─── Newsletters ───

    public function newsletters()
    {
        return view('admin.newsletters.list', ['newsletters' => Newsletter::latest()->paginate(25)]);
    }

    public function newsletterTemplate(Request $request)
    {
        $tpl = EmailTemplate::firstOrCreate(
            ['template_type' => 'newsletter_layout'],
            ['subject' => '', 'html_body' => '{{ content }}', 'is_active' => true]
        );
        if ($request->isMethod('post')) {
            $tpl->update(['html_body' => $request->validate(['html_body' => 'required'])['html_body']]);
            return back()->with('success', 'Template mis à jour.');
        }
        return view('admin.newsletters.template', ['template' => $tpl]);
    }

    public function newsletterCreate(Request $request)
    {
        if ($request->isMethod('post')) {
            $data = $request->validate(['subject' => 'required|max:255', 'html_body' => 'required']);
            Newsletter::create($data);
            return redirect('/admin/newsletters')->with('success', 'Campagne créée.');
        }
        return view('admin.newsletters.form', ['newsletter' => null, 'layout' => $this->getNewsletterLayout()]);
    }

    public function newsletterEdit(string $id, Request $request)
    {
        $nl = Newsletter::findOrFail($id);
        if ($nl->status === 'sent') return redirect('/admin/newsletters')->with('error', 'Campagne déjà envoyée.');
        if ($request->isMethod('post')) {
            $nl->update($request->validate(['subject' => 'required|max:255', 'html_body' => 'required']));
            return redirect('/admin/newsletters')->with('success', 'Campagne mise à jour.');
        }
        return view('admin.newsletters.form', ['newsletter' => $nl, 'layout' => $this->getNewsletterLayout()]);
    }

    public function newsletterSend(string $id, EmailService $mail)
    {
        $nl = Newsletter::findOrFail($id);
        if ($nl->status === 'sent') return back()->with('error', 'Déjà envoyée.');

        $fullHtml = $this->applyNewsletterLayout($nl->html_body);
        $nl->update(['status' => 'sending']);
        $recipients = User::where('is_admin', false)->where('status', '!=', 'deleted')->where('newsletter_optin', true)->whereNotNull('email_verified_at')->get();

        $sent = 0;
        foreach ($recipients as $user) {
            try {
                $mail->sendNewsletterNow($user, $nl->subject, $fullHtml);
                $sent++;
            } catch (\Exception $e) {
                Log::error("Newsletter send failed for {$user->email}: {$e->getMessage()}");
            }
        }

        $nl->update(['status' => 'sent', 'recipients_count' => $sent, 'sent_at' => now()]);
        AuditLog::record('newsletter.send', $nl, ['recipients' => $sent]);
        return back()->with('success', "Newsletter envoyée à {$sent} destinataire(s).");
    }

    public function newsletterPreview(string $id)
    {
        $nl = Newsletter::findOrFail($id);
        $html = $this->applyNewsletterLayout($nl->html_body);
        foreach (['site_name' => config('app.name'), 'site_url' => config('app.url'), 'sujet' => $nl->subject] as $k => $v) {
            $html = str_replace("{{ {$k} }}", $v, $html);
        }
        return response($html)->header('Content-Type', 'text/html');
    }

    public function weeklyNewsletterPreview(\App\Services\NavidromeService $nd)
    {
        try {
            $albums    = $nd->getRecentAlbums(10, now()->subDays(7));
            $topArtists = $nd->getTopPlayedArtists(5);
        } catch (\Exception $e) {
            return response("<pre>Erreur Navidrome : " . htmlspecialchars($e->getMessage()) . "</pre>")
                ->header('Content-Type', 'text/html');
        }

        $html = \App\Console\Commands\SendWeeklyNewMusic::buildEmail($albums, $topArtists);

        // Replace template variables the same way EmailService does
        $ctx = ['site_name' => config('app.name'), 'site_url' => config('app.url')];
        foreach ($ctx as $k => $v) {
            $html = str_replace("{{ {$k} }}", $v, $html);
            $html = str_replace("{{{$k}}}", $v, $html);
        }

        $subscriberCount = \App\Models\User::where('is_admin', false)
            ->where('status', '!=', 'deleted')
            ->where('newsletter_optin', true)
            ->whereNotNull('email_verified_at')
            ->count();

        $newAlbumCount = count($albums);
        $banner = "<div style='font-family:sans-serif;background:#1e1b4b;color:#a5b4fc;padding:10px 16px;font-size:13px;border-bottom:2px solid #4f46e5'>"
            . "⚡ Aperçu — données en temps réel · <strong>{$newAlbumCount}</strong> album(s) ajouté(s) cette semaine · "
            . "<strong>{$subscriberCount}</strong> destinataire(s) · "
            . now()->format('d/m/Y H:i')
            . "</div>";

        return response($banner . $html)->header('Content-Type', 'text/html');
    }

    // ─── Impersonate ───

    public function impersonate(string $id, Request $request)
    {
        $target = User::findOrFail($id);

        if ($target->is_admin) {
            return back()->with('error', 'Impossible d\'usurper un compte administrateur.');
        }
        if ($target->status === 'deleted') {
            return back()->with('error', 'Impossible d\'usurper un compte supprimé.');
        }

        $adminId = Auth::id();
        AuditLog::record('user.impersonate_start', $target, ['admin_id' => $adminId]);

        $request->session()->put('impersonating_admin_id', $adminId);
        Auth::loginUsingId($target->id);

        return redirect('/portal')->with('success', "Vous naviguez en tant que {$target->username}. Utilisez le bouton « Revenir admin » pour reprendre votre session.");
    }

    public function stopImpersonate(Request $request)
    {
        $adminId = $request->session()->pull('impersonating_admin_id');
        if (!$adminId) {
            return redirect('/admin');
        }

        $admin = User::find($adminId);
        if (!$admin || !$admin->is_admin) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect('/login')->with('error', 'Session invalide.');
        }

        $impersonatedUser = Auth::user();
        AuditLog::record('user.impersonate_stop', $impersonatedUser, ['admin_id' => $adminId]);

        Auth::loginUsingId($adminId);

        return redirect("/admin/users/{$impersonatedUser->id}");
    }
}
