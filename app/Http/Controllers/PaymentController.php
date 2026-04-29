<?php

namespace App\Http\Controllers;

use App\Models\{User, Payment, Subscription, Plan, PromoCode, Wallet, WalletTransaction, Notification};
use App\Services\{NavidromeService, StripeService, EmailService};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Log, Hash};

class PaymentController extends Controller
{
    public function success() { return view('portal.payment-success'); }
    public function walletSuccess() { return view('portal.wallet-success'); }
    public function giftSuccess() { return view('portal.gift-success'); }

    public function stripeWebhook(Request $request, StripeService $stripe, NavidromeService $nd, EmailService $mail)
    {
        try {
            $event = $stripe->constructWebhookEvent($request->getContent(), $request->header('Stripe-Signature', ''));
        } catch (\Exception $e) { return response('', 400); }

        $data = $event->data->object;
        try {
            match ($event->type) {
                'checkout.session.completed' => $this->handleCheckout($data, $nd, $mail),
                'invoice.payment_succeeded' => $this->handleInvoiceSuccess($data, $mail),
                'invoice.payment_failed' => $this->handleInvoiceFailed($data),
                default => null,
            };
        } catch (\Exception $e) { Log::error("Webhook error [{$event->type}]: {$e->getMessage()}"); }
        return response('', 200);
    }

    private function handleCheckout($session, NavidromeService $nd, EmailService $mail): void
    {
        $meta = $session->metadata?->toArray() ?? [];
        $userId = $meta['user_id'] ?? null;
        if (!$userId) return;
        $user = User::find($userId);
        if (!$user) return;

        $type = $meta['type'] ?? 'subscription';
        $amount = ($session->amount_total ?? 0) / 100;

        if ($type === 'prepay') {
            $plan = Plan::find($meta['plan_id'] ?? '');
            $months = (int) ($meta['months'] ?? 1);
            if (!$plan) return;
            $days = $plan->period_days * max(1, $months);

            // Prolonge un abonnement existant (active/pending) ou en crée un nouveau
            $sub = Subscription::where('user_id', $user->id)->whereIn('status', ['active', 'pending'])->latest()->first();
            $start = now();
            $end = ($sub && $sub->current_period_end && $sub->current_period_end->isFuture())
                ? $sub->current_period_end->copy()->addDays($days)
                : now()->addDays($days);

            if ($sub) {
                $sub->update(['plan_id' => $plan->id, 'status' => 'active', 'current_period_start' => $sub->current_period_start ?? $start, 'current_period_end' => $end]);
            } else {
                $sub = Subscription::create(['user_id' => $user->id, 'plan_id' => $plan->id, 'status' => 'active', 'current_period_start' => $start, 'current_period_end' => $end]);
            }

            Payment::create(['user_id' => $user->id, 'subscription_id' => $sub->id, 'amount' => $amount, 'stripe_amount' => $amount, 'status' => 'succeeded', 'payment_method' => 'stripe', 'stripe_payment_intent_id' => $session->payment_intent ?? '', 'description' => "{$plan->name} — {$months} mois prépayés"]);
            Notification::send($user->id, 'payment_success', 'Paiement confirmé', "{$plan->name} — {$months} mois prépayés ({$amount}€)", '/portal');

            if ($user->status === 'suspended') $user->update(['status' => 'active']);
            if ($user->navidrome_id) {
                $originalPassword = $user->getDecryptedPassword();
                if ($originalPassword) {
                    try { $nd->reactivateUser($user->navidrome_id, $originalPassword); } catch (\Exception $e) { Log::error($e->getMessage()); }
                }
            }
        } elseif ($type === 'wallet_topup') {
            $wallet = Wallet::firstOrCreate(['user_id' => $user->id]);
            DB::transaction(function () use ($wallet, $amount, $session) {
                $wallet = Wallet::lockForUpdate()->find($wallet->id);
                $wallet->increment('balance', $amount);
                WalletTransaction::create(['wallet_id' => $wallet->id, 'type' => 'topup', 'amount' => $amount, 'description' => 'Rechargement Stripe', 'stripe_payment_intent_id' => $session->payment_intent ?? '']);
            });
            Payment::create(['user_id' => $user->id, 'amount' => $amount, 'stripe_amount' => $amount, 'status' => 'succeeded', 'payment_method' => 'stripe', 'stripe_payment_intent_id' => $session->payment_intent ?? '', 'description' => 'Rechargement portefeuille']);
            Notification::send($user->id, 'payment_success', 'Portefeuille rechargé', "Rechargement de {$amount}€ effectué.", '/portal/wallet');
        } elseif ($type === 'gift') {
            $plan = Plan::find($meta['plan_id'] ?? '');
            $recipientEmail = $meta['recipient_email'] ?? '';
            if (!$plan || !$recipientEmail) return;
            $recipient = User::where('email', $recipientEmail)->first();
            if (!$recipient) {
                $pw = bin2hex(random_bytes(8));
                $recipient = User::create(['username' => explode('@', $recipientEmail)[0] . rand(10, 99), 'email' => $recipientEmail, 'password' => Hash::make($pw)]);
                $recipient->storeEncryptedPassword($pw);
                Wallet::create(['user_id' => $recipient->id]);
                try { $r = $nd->createUser($recipient->username, $pw, '', $recipientEmail); $recipient->update(['navidrome_id' => $r['id'] ?? null]); } catch (\Exception $e) {}
            }
            Subscription::create(['user_id' => $recipient->id, 'plan_id' => $plan->id, 'status' => 'active', 'is_gift' => true, 'gifted_by' => $user->id, 'gift_recipient_email' => $recipientEmail, 'current_period_start' => now(), 'current_period_end' => now()->addDays($plan->period_days)]);
            Payment::create(['user_id' => $user->id, 'amount' => $amount, 'stripe_amount' => $amount, 'status' => 'succeeded', 'payment_method' => 'stripe', 'stripe_payment_intent_id' => $session->payment_intent ?? '', 'description' => "Cadeau {$plan->name} pour {$recipientEmail}"]);
            // Ouvrir l'accès Navidrome au destinataire (cas user existant qui était suspendu)
            if ($recipient->navidrome_id) {
                $rpw = $recipient->getDecryptedPassword();
                if ($rpw) { try { $nd->reactivateUser($recipient->navidrome_id, $rpw); } catch (\Exception $e) {} }
            }
            try { $mail->sendGiftReceived($recipientEmail, $plan->name); } catch (\Exception $e) {}
        } else {
            $sub = Subscription::where('user_id', $user->id)->where('status', 'pending')->latest()->first();
            if ($sub) {
                $sub->update(['status' => 'active', 'stripe_subscription_id' => $session->subscription ?? '', 'current_period_start' => now(), 'current_period_end' => now()->addDays($sub->plan->period_days)]);
                if ($sub->promo_code_id) PromoCode::where('id', $sub->promo_code_id)->increment('current_uses');
            }
            Payment::create(['user_id' => $user->id, 'subscription_id' => $sub?->id, 'amount' => $amount, 'stripe_amount' => $amount, 'status' => 'succeeded', 'payment_method' => 'stripe', 'stripe_payment_intent_id' => $session->payment_intent ?? '', 'description' => 'Abonnement ' . ($sub?->plan?->name ?? '')]);
            Notification::send($user->id, 'payment_success', 'Abonnement activé', 'Votre abonnement ' . ($sub?->plan?->name ?? '') . " est maintenant actif.", '/portal');

            // Si suspendu (impayé), on remet le statut user/sub en actif
            if ($user->status === 'suspended') {
                $user->update(['status' => 'active']);
                Subscription::where('user_id', $user->id)->where('status', 'suspended')->update(['status' => 'active']);
            }
            // Toujours (re)donner accès à Navidrome au paiement : premier achat OU réactivation
            if ($user->navidrome_id) {
                $originalPassword = $user->getDecryptedPassword();
                if ($originalPassword) {
                    try { app(NavidromeService::class)->reactivateUser($user->navidrome_id, $originalPassword); } catch (\Exception $e) { Log::error($e->getMessage()); }
                }
            }
        }
    }

    private function handleInvoiceSuccess($invoice, EmailService $mail): void
    {
        $user = User::where('stripe_customer_id', $invoice->customer)->first();
        if (!$user) return;
        $sub = $user->activeSubscription;
        if ($sub) { $sub->update(['current_period_start' => now(), 'current_period_end' => now()->addDays($sub->plan->period_days)]); }
        $amount = ($invoice->amount_paid ?? 0) / 100;
        Payment::create(['user_id' => $user->id, 'subscription_id' => $sub?->id, 'amount' => $amount, 'stripe_amount' => $amount, 'status' => 'succeeded', 'payment_method' => 'stripe', 'stripe_invoice_id' => $invoice->id ?? '', 'description' => 'Renouvellement']);
        Notification::send($user->id, 'subscription_renewed', 'Abonnement renouvelé', "Votre abonnement a été renouvelé ({$amount}€).", '/portal');
    }

    private function handleInvoiceFailed($invoice): void
    {
        $user = User::where('stripe_customer_id', $invoice->customer)->first();
        if (!$user) return;
        $amount = ($invoice->amount_due ?? 0) / 100;
        Payment::create(['user_id' => $user->id, 'amount' => $amount, 'status' => 'failed', 'payment_method' => 'stripe', 'stripe_invoice_id' => $invoice->id ?? '', 'description' => 'Échec paiement']);
        Notification::send($user->id, 'payment_failed', 'Échec de paiement', "Le prélèvement de {$amount}€ a échoué. Veuillez régulariser.", '/portal/plans');
    }
}
