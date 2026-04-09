import json
import logging
from datetime import timedelta
from decimal import Decimal

from django.conf import settings
from django.contrib import messages
from django.contrib.auth.decorators import login_required
from django.http import HttpResponse, JsonResponse
from django.shortcuts import redirect, render
from django.utils import timezone
from django.views.decorators.csrf import csrf_exempt
from django.views.decorators.http import require_POST

from apps.accounts.models import User, Wallet, WalletTransaction
from apps.payments.models import Payment
from apps.subscriptions.models import Plan, Subscription
from services.email_service import email_service
from services.navidrome import navidrome_client
from services.stripe_service import stripe_service

logger = logging.getLogger(__name__)


@login_required
def payment_success(request):
    session_id = request.GET.get('session_id')
    messages.success(request, "Paiement réussi ! Votre abonnement est maintenant actif.")
    return render(request, 'payments/success.html')


@login_required
def wallet_success(request):
    session_id = request.GET.get('session_id')
    messages.success(request, "Votre portefeuille a été rechargé avec succès.")
    return render(request, 'payments/wallet_success.html')


@login_required
def gift_success(request):
    session_id = request.GET.get('session_id')
    messages.success(request, "L'abonnement cadeau a été envoyé avec succès !")
    return render(request, 'payments/gift_success.html')


@csrf_exempt
@require_POST
def stripe_webhook(request):
    """Handle Stripe webhooks."""
    payload = request.body
    sig_header = request.META.get('HTTP_STRIPE_SIGNATURE', '')

    try:
        event = stripe_service.construct_webhook_event(payload, sig_header)
    except Exception as e:
        logger.error("Stripe webhook signature verification failed: %s", e)
        return HttpResponse(status=400)

    event_type = event['type']
    data = event['data']['object']

    try:
        if event_type == 'checkout.session.completed':
            _handle_checkout_completed(data)
        elif event_type == 'invoice.payment_succeeded':
            _handle_invoice_payment_succeeded(data)
        elif event_type == 'invoice.payment_failed':
            _handle_invoice_payment_failed(data)
        elif event_type == 'customer.subscription.deleted':
            _handle_subscription_deleted(data)
    except Exception:
        logger.exception("Error handling Stripe webhook event %s", event_type)

    return HttpResponse(status=200)


def _handle_checkout_completed(session):
    """Handle successful checkout."""
    metadata = session.get('metadata', {})
    user_id = metadata.get('user_id')
    session_type = metadata.get('type', '')

    if not user_id:
        return

    try:
        user = User.objects.get(pk=user_id)
    except User.DoesNotExist:
        logger.error("User %s not found for checkout session", user_id)
        return

    if session_type == 'wallet_topup':
        _handle_wallet_topup(user, metadata, session)
    elif session_type == 'gift':
        _handle_gift_purchase(user, metadata, session)
    else:
        _handle_subscription_checkout(user, metadata, session)


def _handle_subscription_checkout(user, metadata, session):
    """Activate subscription after checkout."""
    plan_id = metadata.get('plan_id')
    stripe_subscription_id = session.get('subscription', '')

    sub = Subscription.objects.filter(
        user=user, status=Subscription.Status.PENDING
    ).order_by('-created_at').first()

    if sub:
        sub.status = Subscription.Status.ACTIVE
        sub.stripe_subscription_id = stripe_subscription_id
        sub.current_period_start = timezone.now()
        plan = sub.plan
        if plan.billing_cycle == Plan.BillingCycle.MONTHLY:
            sub.current_period_end = timezone.now() + timedelta(days=30)
        elif plan.billing_cycle == Plan.BillingCycle.QUARTERLY:
            sub.current_period_end = timezone.now() + timedelta(days=90)
        else:
            sub.current_period_end = timezone.now() + timedelta(days=365)
        sub.save()

        # Increment promo code usage
        if sub.promo_code:
            sub.promo_code.current_uses += 1
            sub.promo_code.save(update_fields=['current_uses'])

    # Create payment record
    amount = Decimal(str(session.get('amount_total', 0))) / 100
    Payment.objects.create(
        user=user,
        subscription=sub,
        amount=amount,
        stripe_amount=amount,
        status=Payment.Status.SUCCEEDED,
        payment_method=Payment.PaymentMethod.STRIPE,
        stripe_payment_intent_id=session.get('payment_intent', ''),
        description=f"Abonnement {sub.plan.name}" if sub else "Abonnement",
    )

    # Ensure user is active
    if user.status != User.Status.ACTIVE:
        user.status = User.Status.ACTIVE
        user.is_active = True
        user.save(update_fields=['status', 'is_active'])

    logger.info("Subscription activated for user %s", user.id)


def _handle_wallet_topup(user, metadata, session):
    """Credit wallet after topup."""
    amount = Decimal(metadata.get('amount', '0'))
    wallet, _ = Wallet.objects.get_or_create(user=user)
    wallet.balance += amount
    wallet.save(update_fields=['balance'])

    WalletTransaction.objects.create(
        wallet=wallet,
        type=WalletTransaction.Type.TOPUP,
        amount=amount,
        description='Rechargement via Stripe',
        stripe_payment_intent_id=session.get('payment_intent', ''),
    )

    Payment.objects.create(
        user=user,
        amount=amount,
        stripe_amount=amount,
        status=Payment.Status.SUCCEEDED,
        payment_method=Payment.PaymentMethod.STRIPE,
        stripe_payment_intent_id=session.get('payment_intent', ''),
        description='Rechargement portefeuille',
    )

    logger.info("Wallet topped up for user %s: %s€", user.id, amount)


def _handle_gift_purchase(buyer, metadata, session):
    """Process gift subscription purchase."""
    plan_id = metadata.get('plan_id')
    recipient_email = metadata.get('recipient_email', '')

    try:
        plan = Plan.objects.get(pk=plan_id)
    except Plan.DoesNotExist:
        logger.error("Plan %s not found for gift", plan_id)
        return

    # Find or create recipient
    recipient, created = User.objects.get_or_create(
        email=recipient_email,
        defaults={
            'username': recipient_email.split('@')[0],
            'is_active': True,
        },
    )

    if created:
        import secrets
        raw_password = secrets.token_urlsafe(12)
        recipient.set_password(raw_password)
        recipient.save()

        # Create in Navidrome
        try:
            nd_user = navidrome_client.create_user(
                username=recipient.username,
                password=raw_password,
                email=recipient.email,
            )
            recipient.navidrome_id = nd_user.get('id', '')
            recipient.save(update_fields=['navidrome_id'])
        except Exception:
            logger.exception("Failed to create Navidrome user for gift recipient %s", recipient.email)

    # Create subscription for recipient
    sub = Subscription.objects.create(
        user=recipient,
        plan=plan,
        status=Subscription.Status.ACTIVE,
        is_gift=True,
        gifted_by=buyer,
        gift_recipient_email=recipient_email,
        current_period_start=timezone.now(),
        current_period_end=timezone.now() + timedelta(days=30 if plan.billing_cycle == Plan.BillingCycle.MONTHLY else 365),
    )

    # Payment record
    amount = Decimal(str(session.get('amount_total', 0))) / 100
    Payment.objects.create(
        user=buyer,
        subscription=sub,
        amount=amount,
        stripe_amount=amount,
        status=Payment.Status.SUCCEEDED,
        payment_method=Payment.PaymentMethod.STRIPE,
        stripe_payment_intent_id=session.get('payment_intent', ''),
        description=f"Abonnement cadeau {plan.name} pour {recipient_email}",
    )

    # Send notification
    try:
        email_service.send_gift_received(recipient_email, buyer, plan)
    except Exception:
        logger.exception("Failed to send gift email to %s", recipient_email)

    logger.info("Gift subscription created: %s -> %s", buyer.id, recipient_email)


def _handle_invoice_payment_succeeded(invoice):
    """Handle recurring payment success."""
    customer_id = invoice.get('customer', '')
    try:
        user = User.objects.get(stripe_customer_id=customer_id)
    except User.DoesNotExist:
        return

    sub = Subscription.objects.filter(
        user=user, status=Subscription.Status.ACTIVE
    ).first()

    if sub:
        # Extend period
        plan = sub.plan
        sub.current_period_start = timezone.now()
        if plan.billing_cycle == Plan.BillingCycle.MONTHLY:
            sub.current_period_end = timezone.now() + timedelta(days=30)
        elif plan.billing_cycle == Plan.BillingCycle.QUARTERLY:
            sub.current_period_end = timezone.now() + timedelta(days=90)
        else:
            sub.current_period_end = timezone.now() + timedelta(days=365)
        sub.save()

    amount = Decimal(str(invoice.get('amount_paid', 0))) / 100
    Payment.objects.create(
        user=user,
        subscription=sub,
        amount=amount,
        stripe_amount=amount,
        status=Payment.Status.SUCCEEDED,
        payment_method=Payment.PaymentMethod.STRIPE,
        stripe_payment_intent_id=invoice.get('payment_intent', ''),
        stripe_invoice_id=invoice.get('id', ''),
        description='Renouvellement abonnement',
    )

    try:
        email_service.send_payment_success(user, None)
    except Exception:
        logger.exception("Failed to send payment success email")


def _handle_invoice_payment_failed(invoice):
    """Handle failed recurring payment."""
    customer_id = invoice.get('customer', '')
    try:
        user = User.objects.get(stripe_customer_id=customer_id)
    except User.DoesNotExist:
        return

    amount = Decimal(str(invoice.get('amount_due', 0))) / 100
    Payment.objects.create(
        user=user,
        amount=amount,
        stripe_amount=amount,
        status=Payment.Status.FAILED,
        payment_method=Payment.PaymentMethod.STRIPE,
        stripe_invoice_id=invoice.get('id', ''),
        description='Échec de paiement abonnement',
    )

    logger.warning("Payment failed for user %s", user.id)


def _handle_subscription_deleted(subscription_data):
    """Handle Stripe subscription cancellation."""
    customer_id = subscription_data.get('customer', '')
    stripe_sub_id = subscription_data.get('id', '')

    try:
        sub = Subscription.objects.get(stripe_subscription_id=stripe_sub_id)
        sub.status = Subscription.Status.CANCELLED
        sub.cancelled_at = timezone.now()
        sub.save()
    except Subscription.DoesNotExist:
        pass
