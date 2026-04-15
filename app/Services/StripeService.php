<?php

namespace App\Services;

use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Checkout\Session;
use Stripe\Refund as StripeRefund;
use Stripe\Subscription as StripeSub;
use Stripe\Webhook;
use App\Models\User;

class StripeService
{
    public function getOrCreateCustomer(User $user): Customer
    {
        if ($user->stripe_customer_id) {
            try { return Customer::retrieve($user->stripe_customer_id); } catch (\Exception $e) {}
        }
        $customer = Customer::create([
            'email' => $user->email,
            'name' => $user->full_name,
            'metadata' => ['user_id' => $user->id],
        ]);
        $user->update(['stripe_customer_id' => $customer->id]);
        return $customer;
    }

    public function createCheckoutSession(User $user, \App\Models\Plan $plan, string $successUrl, string $cancelUrl): Session
    {
        $customer = $this->getOrCreateCustomer($user);
        return Session::create([
            'customer' => $customer->id,
            'payment_method_types' => ['card'],
            'mode' => 'subscription',
            'line_items' => [['price' => $plan->stripe_price_id, 'quantity' => 1]],
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => ['user_id' => $user->id, 'plan_id' => $plan->id],
        ]);
    }

    public function createWalletTopupSession(User $user, int $amountCents, string $successUrl, string $cancelUrl): Session
    {
        $customer = $this->getOrCreateCustomer($user);
        return Session::create([
            'customer' => $customer->id,
            'payment_method_types' => ['card'],
            'mode' => 'payment',
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => $amountCents,
                    'product_data' => ['name' => 'Rechargement portefeuille'],
                ],
                'quantity' => 1,
            ]],
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => ['user_id' => $user->id, 'type' => 'wallet_topup', 'amount' => $amountCents / 100],
        ]);
    }

    public function createGiftSession(User $buyer, \App\Models\Plan $plan, string $recipientEmail, string $successUrl, string $cancelUrl): Session
    {
        $customer = $this->getOrCreateCustomer($buyer);
        return Session::create([
            'customer' => $customer->id,
            'payment_method_types' => ['card'],
            'mode' => 'payment',
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => (int)($plan->price * 100),
                    'product_data' => ['name' => "Abonnement cadeau — {$plan->name}"],
                ],
                'quantity' => 1,
            ]],
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => ['user_id' => $buyer->id, 'plan_id' => $plan->id, 'type' => 'gift', 'recipient_email' => $recipientEmail],
        ]);
    }

    public function cancelSubscription(string $stripeSubId): StripeSub
    {
        return StripeSub::update($stripeSubId, ['cancel_at_period_end' => true]);
    }

    public function cancelSubscriptionNow(string $stripeSubId): StripeSub
    {
        $sub = StripeSub::retrieve($stripeSubId);
        $sub->cancel();
        return $sub;
    }

    public function createRefund(string $paymentIntentId, ?int $amountCents = null): StripeRefund
    {
        $params = ['payment_intent' => $paymentIntentId];
        if ($amountCents) $params['amount'] = $amountCents;
        return StripeRefund::create($params);
    }

    public function constructWebhookEvent(string $payload, string $sigHeader): \Stripe\Event
    {
        return Webhook::constructEvent($payload, $sigHeader, config('services.stripe.webhook_secret'));
    }
}
