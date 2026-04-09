import logging

from django.conf import settings
from django.contrib import messages
from django.contrib.auth.decorators import login_required
from django.shortcuts import get_object_or_404, redirect, render
from django.utils import timezone

from apps.accounts.forms import ChangePasswordForm, ProfileForm
from apps.accounts.models import User, Wallet, WalletTransaction, UserDevice
from apps.payments.models import Payment
from apps.subscriptions.models import Plan, PromoCode, Subscription
from services.navidrome import navidrome_client
from services.stripe_service import stripe_service

logger = logging.getLogger(__name__)


@login_required
def dashboard(request):
    user = request.user
    active_sub = Subscription.objects.filter(user=user, status=Subscription.Status.ACTIVE).first()
    wallet = Wallet.objects.filter(user=user).first()
    recent_payments = Payment.objects.filter(user=user)[:5]
    return render(request, 'customer_portal/dashboard.html', {
        'active_sub': active_sub,
        'wallet': wallet,
        'recent_payments': recent_payments,
    })


# ─── Profile & Password ─────────────────────────────────────────────────────

@login_required
def profile(request):
    if request.method == 'POST':
        form = ProfileForm(request.POST, instance=request.user)
        if form.is_valid():
            form.save()
            # Sync email/name to Navidrome
            if request.user.navidrome_id:
                try:
                    navidrome_client.update_user(
                        request.user.navidrome_id,
                        name=f"{request.user.first_name} {request.user.last_name}".strip(),
                        email=request.user.email,
                    )
                except Exception:
                    logger.exception("Failed to sync profile to Navidrome")
            messages.success(request, "Profil mis à jour.")
            return redirect('customer_portal:profile')
    else:
        form = ProfileForm(instance=request.user)
    return render(request, 'customer_portal/profile.html', {'form': form})


@login_required
def change_password(request):
    if request.method == 'POST':
        form = ChangePasswordForm(request.POST)
        if form.is_valid():
            if not request.user.check_password(form.cleaned_data['current_password']):
                messages.error(request, "Le mot de passe actuel est incorrect.")
            else:
                raw_password = form.cleaned_data['new_password']
                request.user.set_password(raw_password)
                request.user.save()

                # Sync to Navidrome
                if request.user.navidrome_id:
                    try:
                        navidrome_client.change_password(request.user.navidrome_id, raw_password)
                    except Exception:
                        logger.exception("Failed to sync password to Navidrome")

                messages.success(request, "Mot de passe modifié avec succès. Veuillez vous reconnecter.")
                return redirect('accounts:login')
    else:
        form = ChangePasswordForm()
    return render(request, 'customer_portal/change_password.html', {'form': form})


# ─── Subscription Management ────────────────────────────────────────────────

@login_required
def subscription_plans(request):
    plans = Plan.objects.filter(is_active=True)
    active_sub = Subscription.objects.filter(user=request.user, status=Subscription.Status.ACTIVE).first()
    return render(request, 'customer_portal/plans.html', {
        'plans': plans,
        'active_sub': active_sub,
    })


@login_required
def subscribe(request, plan_id):
    plan = get_object_or_404(Plan, pk=plan_id, is_active=True)

    # Check if already subscribed
    existing = Subscription.objects.filter(user=request.user, status=Subscription.Status.ACTIVE).first()
    if existing:
        messages.warning(request, "Vous avez déjà un abonnement actif.")
        return redirect('customer_portal:dashboard')

    promo_code_str = request.GET.get('promo', '')
    promo = None
    if promo_code_str:
        try:
            promo = PromoCode.objects.get(code=promo_code_str)
            if not promo.is_valid:
                promo = None
                messages.warning(request, "Code promo invalide ou expiré.")
        except PromoCode.DoesNotExist:
            messages.warning(request, "Code promo introuvable.")

    success_url = f"{settings.SITE_URL}/payments/success/?session_id={{CHECKOUT_SESSION_ID}}"
    cancel_url = f"{settings.SITE_URL}/portal/plans/"

    try:
        session = stripe_service.create_checkout_session(
            user=request.user,
            plan=plan,
            success_url=success_url,
            cancel_url=cancel_url,
            promo_code=promo,
        )

        # Create pending subscription
        Subscription.objects.create(
            user=request.user,
            plan=plan,
            status=Subscription.Status.PENDING,
            promo_code=promo,
        )

        return redirect(session.url)
    except Exception as e:
        logger.exception("Failed to create checkout session")
        messages.error(request, f"Erreur lors de la création du paiement: {e}")
        return redirect('customer_portal:subscription_plans')


@login_required
def cancel_subscription(request):
    if request.method == 'POST':
        sub = Subscription.objects.filter(user=request.user, status=Subscription.Status.ACTIVE).first()
        if sub:
            if sub.stripe_subscription_id:
                try:
                    stripe_service.cancel_subscription(sub.stripe_subscription_id)
                except Exception:
                    logger.exception("Failed to cancel Stripe subscription")
            sub.status = Subscription.Status.CANCELLED
            sub.cancelled_at = timezone.now()
            sub.save()
            messages.success(request, "Votre abonnement sera annulé à la fin de la période en cours.")
        else:
            messages.warning(request, "Aucun abonnement actif trouvé.")
    return redirect('customer_portal:dashboard')


# ─── Wallet ──────────────────────────────────────────────────────────────────

@login_required
def wallet_view(request):
    wallet, _ = Wallet.objects.get_or_create(user=request.user)
    transactions = WalletTransaction.objects.filter(wallet=wallet)[:30]
    return render(request, 'customer_portal/wallet.html', {
        'wallet': wallet,
        'transactions': transactions,
    })


@login_required
def wallet_topup(request):
    if request.method == 'POST':
        amount = request.POST.get('amount', '10')
        try:
            from decimal import Decimal
            amount = Decimal(amount)
            if amount < 5:
                messages.error(request, "Le montant minimum est de 5€.")
                return redirect('customer_portal:wallet')

            success_url = f"{settings.SITE_URL}/payments/wallet-success/?session_id={{CHECKOUT_SESSION_ID}}"
            cancel_url = f"{settings.SITE_URL}/portal/wallet/"

            session = stripe_service.create_wallet_topup_session(
                user=request.user,
                amount=amount,
                success_url=success_url,
                cancel_url=cancel_url,
            )
            return redirect(session.url)
        except Exception as e:
            logger.exception("Failed to create wallet topup session")
            messages.error(request, f"Erreur: {e}")

    return redirect('customer_portal:wallet')


# ─── Gift Subscription ───────────────────────────────────────────────────────

@login_required
def gift_subscription(request):
    plans = Plan.objects.filter(is_active=True)
    if request.method == 'POST':
        plan_id = request.POST.get('plan_id')
        recipient_email = request.POST.get('recipient_email', '')
        plan = get_object_or_404(Plan, pk=plan_id, is_active=True)

        if not recipient_email:
            messages.error(request, "Veuillez saisir l'email du destinataire.")
            return render(request, 'customer_portal/gift.html', {'plans': plans})

        success_url = f"{settings.SITE_URL}/payments/gift-success/?session_id={{CHECKOUT_SESSION_ID}}"
        cancel_url = f"{settings.SITE_URL}/portal/gift/"

        try:
            session = stripe_service.create_gift_checkout_session(
                buyer=request.user,
                plan=plan,
                recipient_email=recipient_email,
                success_url=success_url,
                cancel_url=cancel_url,
            )
            return redirect(session.url)
        except Exception as e:
            logger.exception("Failed to create gift checkout session")
            messages.error(request, f"Erreur: {e}")

    return render(request, 'customer_portal/gift.html', {'plans': plans})


# ─── Devices ─────────────────────────────────────────────────────────────────

@login_required
def devices(request):
    user_devices = UserDevice.objects.filter(user=request.user, is_active=True)
    return render(request, 'customer_portal/devices.html', {'devices': user_devices})


@login_required
def revoke_device(request, device_id):
    if request.method == 'POST':
        device = get_object_or_404(UserDevice, pk=device_id, user=request.user)
        device.is_active = False
        device.save(update_fields=['is_active'])
        messages.success(request, f"Accès révoqué pour {device.device_name}.")
    return redirect('customer_portal:devices')


# ─── Payment History ─────────────────────────────────────────────────────────

@login_required
def payment_history(request):
    payments = Payment.objects.filter(user=request.user)
    return render(request, 'customer_portal/payments.html', {'payments': payments})
