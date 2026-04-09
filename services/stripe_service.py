"""Stripe integration service for payments, subscriptions, and refunds."""

import logging
from decimal import Decimal

import stripe
from django.conf import settings

logger = logging.getLogger(__name__)

stripe.api_key = settings.STRIPE_SECRET_KEY


class StripeService:
    """Service for Stripe payment operations."""

    def create_customer(self, user):
        """Create a Stripe customer for a user."""
        customer = stripe.Customer.create(
            email=user.email,
            name=f"{user.first_name} {user.last_name}".strip() or user.username,
            metadata={'user_id': str(user.id)},
        )
        user.stripe_customer_id = customer.id
        user.save(update_fields=['stripe_customer_id'])
        logger.info("Created Stripe customer %s for user %s", customer.id, user.id)
        return customer

    def get_or_create_customer(self, user):
        """Get existing or create new Stripe customer."""
        if user.stripe_customer_id:
            try:
                return stripe.Customer.retrieve(user.stripe_customer_id)
            except stripe.error.InvalidRequestError:
                pass
        return self.create_customer(user)

    def create_checkout_session(self, user, plan, success_url, cancel_url, promo_code=None):
        """Create a Stripe Checkout session for a subscription."""
        customer = self.get_or_create_customer(user)

        params = {
            'customer': customer.id,
            'payment_method_types': ['card'],
            'mode': 'subscription',
            'line_items': [{
                'price': plan.stripe_price_id,
                'quantity': 1,
            }],
            'success_url': success_url,
            'cancel_url': cancel_url,
            'metadata': {
                'user_id': str(user.id),
                'plan_id': str(plan.id),
            },
        }

        if promo_code and promo_code.is_valid:
            # Apply discount via Stripe coupon if available
            params['metadata']['promo_code'] = promo_code.code

        session = stripe.checkout.Session.create(**params)
        logger.info("Created checkout session %s for user %s", session.id, user.id)
        return session

    def create_wallet_topup_session(self, user, amount, success_url, cancel_url):
        """Create a Stripe Checkout session to top up wallet."""
        customer = self.get_or_create_customer(user)

        session = stripe.checkout.Session.create(
            customer=customer.id,
            payment_method_types=['card'],
            mode='payment',
            line_items=[{
                'price_data': {
                    'currency': 'eur',
                    'unit_amount': int(Decimal(str(amount)) * 100),
                    'product_data': {
                        'name': 'Rechargement portefeuille',
                    },
                },
                'quantity': 1,
            }],
            success_url=success_url,
            cancel_url=cancel_url,
            metadata={
                'user_id': str(user.id),
                'type': 'wallet_topup',
                'amount': str(amount),
            },
        )
        logger.info("Created wallet topup session %s for user %s (amount: %s€)", session.id, user.id, amount)
        return session

    def create_gift_checkout_session(self, buyer, plan, recipient_email, success_url, cancel_url):
        """Create a checkout session for gifting a subscription."""
        customer = self.get_or_create_customer(buyer)

        session = stripe.checkout.Session.create(
            customer=customer.id,
            payment_method_types=['card'],
            mode='payment',
            line_items=[{
                'price_data': {
                    'currency': 'eur',
                    'unit_amount': int(plan.price * 100),
                    'product_data': {
                        'name': f'Abonnement cadeau — {plan.name}',
                    },
                },
                'quantity': 1,
            }],
            success_url=success_url,
            cancel_url=cancel_url,
            metadata={
                'user_id': str(buyer.id),
                'plan_id': str(plan.id),
                'type': 'gift',
                'recipient_email': recipient_email,
            },
        )
        logger.info("Created gift session %s for %s -> %s", session.id, buyer.id, recipient_email)
        return session

    def cancel_subscription(self, stripe_subscription_id):
        """Cancel a Stripe subscription."""
        sub = stripe.Subscription.modify(
            stripe_subscription_id,
            cancel_at_period_end=True,
        )
        logger.info("Cancelled Stripe subscription %s", stripe_subscription_id)
        return sub

    def cancel_subscription_immediately(self, stripe_subscription_id):
        """Cancel a Stripe subscription immediately."""
        sub = stripe.Subscription.cancel(stripe_subscription_id)
        logger.info("Immediately cancelled Stripe subscription %s", stripe_subscription_id)
        return sub

    def create_refund(self, payment_intent_id, amount=None):
        """Create a refund on Stripe."""
        params = {'payment_intent': payment_intent_id}
        if amount:
            params['amount'] = int(Decimal(str(amount)) * 100)

        refund = stripe.Refund.create(**params)
        logger.info("Created Stripe refund %s for PI %s", refund.id, payment_intent_id)
        return refund

    def get_payment_methods(self, user):
        """Get saved payment methods for a customer."""
        if not user.stripe_customer_id:
            return []
        methods = stripe.PaymentMethod.list(
            customer=user.stripe_customer_id,
            type='card',
        )
        return methods.data

    def construct_webhook_event(self, payload, sig_header):
        """Construct and verify a webhook event."""
        return stripe.Webhook.construct_event(
            payload, sig_header, settings.STRIPE_WEBHOOK_SECRET
        )


stripe_service = StripeService()
