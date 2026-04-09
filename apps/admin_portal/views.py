import logging

from django.contrib import messages
from django.contrib.auth.decorators import login_required, user_passes_test
from django.db.models import Count, Sum, Q
from django.shortcuts import get_object_or_404, redirect, render
from django.utils import timezone

from apps.accounts.forms import AdminUserForm, SMTPConfigForm, EmailTemplateForm
from apps.accounts.models import User, Wallet, WalletTransaction, SMTPConfiguration, EmailTemplate
from apps.payments.models import Payment, Refund
from apps.subscriptions.forms import PlanForm, PromoCodeForm, SubscriptionForm
from apps.subscriptions.models import Plan, PromoCode, Subscription
from apps.support.models import Ticket, TicketMessage
from services.email_service import email_service
from services.navidrome import navidrome_client
from services.stripe_service import stripe_service

logger = logging.getLogger(__name__)


def staff_required(view_func):
    return login_required(user_passes_test(lambda u: u.is_staff)(view_func))


# ─── Dashboard ───────────────────────────────────────────────────────────────

@staff_required
def dashboard(request):
    now = timezone.now()
    context = {
        'total_users': User.objects.filter(is_superuser=False).count(),
        'active_users': User.objects.filter(status=User.Status.ACTIVE, is_superuser=False).count(),
        'suspended_users': User.objects.filter(status=User.Status.SUSPENDED).count(),
        'active_subscriptions': Subscription.objects.filter(status=Subscription.Status.ACTIVE).count(),
        'revenue_month': Payment.objects.filter(
            status=Payment.Status.SUCCEEDED,
            created_at__year=now.year,
            created_at__month=now.month,
        ).aggregate(total=Sum('amount'))['total'] or 0,
        'open_tickets': Ticket.objects.filter(status__in=['open', 'in_progress']).count(),
        'recent_payments': Payment.objects.select_related('user')[:10],
        'recent_tickets': Ticket.objects.select_related('user')[:5],
    }
    return render(request, 'admin_portal/dashboard.html', context)


# ─── User Management ────────────────────────────────────────────────────────

@staff_required
def user_list(request):
    status_filter = request.GET.get('status', '')
    search = request.GET.get('q', '')
    users = User.objects.filter(is_superuser=False)
    if status_filter:
        users = users.filter(status=status_filter)
    if search:
        users = users.filter(
            Q(username__icontains=search) |
            Q(email__icontains=search) |
            Q(first_name__icontains=search) |
            Q(last_name__icontains=search)
        )
    return render(request, 'admin_portal/users/list.html', {
        'users': users,
        'status_filter': status_filter,
        'search': search,
    })


@staff_required
def user_create(request):
    if request.method == 'POST':
        form = AdminUserForm(request.POST)
        if form.is_valid():
            user = form.save(commit=False)
            raw_password = form.cleaned_data.get('password')
            if raw_password:
                user.set_password(raw_password)
            user.save()

            # Create in Navidrome
            if raw_password:
                try:
                    nd_user = navidrome_client.create_user(
                        username=user.username,
                        password=raw_password,
                        name=f"{user.first_name} {user.last_name}".strip(),
                        email=user.email,
                    )
                    user.navidrome_id = nd_user.get('id', '')
                    user.save(update_fields=['navidrome_id'])
                except Exception:
                    logger.exception("Failed to create Navidrome user for %s", user.username)

            try:
                email_service.send_welcome(user)
            except Exception:
                logger.exception("Failed to send welcome email")

            messages.success(request, f"Utilisateur {user.username} créé avec succès.")
            return redirect('admin_portal:user_list')
    else:
        form = AdminUserForm()
    return render(request, 'admin_portal/users/form.html', {'form': form, 'title': 'Nouvel utilisateur'})


@staff_required
def user_edit(request, user_id):
    user = get_object_or_404(User, pk=user_id)
    if request.method == 'POST':
        form = AdminUserForm(request.POST, instance=user)
        if form.is_valid():
            user = form.save(commit=False)
            raw_password = form.cleaned_data.get('password')
            if raw_password:
                user.set_password(raw_password)
                # Sync to Navidrome
                if user.navidrome_id:
                    try:
                        navidrome_client.change_password(user.navidrome_id, raw_password)
                    except Exception:
                        logger.exception("Failed to sync password to Navidrome")
            user.save()
            messages.success(request, f"Utilisateur {user.username} mis à jour.")
            return redirect('admin_portal:user_list')
    else:
        form = AdminUserForm(instance=user)
    return render(request, 'admin_portal/users/form.html', {'form': form, 'title': f'Modifier {user.username}', 'user_obj': user})


@staff_required
def user_detail(request, user_id):
    user = get_object_or_404(User, pk=user_id)
    wallet = Wallet.objects.filter(user=user).first()
    subscriptions = Subscription.objects.filter(user=user)
    payments = Payment.objects.filter(user=user)[:20]
    return render(request, 'admin_portal/users/detail.html', {
        'user_obj': user,
        'wallet': wallet,
        'subscriptions': subscriptions,
        'payments': payments,
    })


@staff_required
def user_suspend(request, user_id):
    user = get_object_or_404(User, pk=user_id)
    if request.method == 'POST':
        user.status = User.Status.SUSPENDED
        user.is_active = False
        user.save(update_fields=['status', 'is_active'])
        Subscription.objects.filter(user=user, status=Subscription.Status.ACTIVE).update(
            status=Subscription.Status.SUSPENDED
        )
        try:
            email_service.send_account_suspended(user)
        except Exception:
            logger.exception("Failed to send suspension email")
        messages.success(request, f"Utilisateur {user.username} suspendu.")
    return redirect('admin_portal:user_detail', user_id=user_id)


@staff_required
def user_reactivate(request, user_id):
    user = get_object_or_404(User, pk=user_id)
    if request.method == 'POST':
        user.status = User.Status.ACTIVE
        user.is_active = True
        user.save(update_fields=['status', 'is_active'])
        messages.success(request, f"Utilisateur {user.username} réactivé.")
    return redirect('admin_portal:user_detail', user_id=user_id)


@staff_required
def user_delete(request, user_id):
    user = get_object_or_404(User, pk=user_id)
    if request.method == 'POST':
        # Delete from Navidrome
        if user.navidrome_id:
            try:
                navidrome_client.delete_user(user.navidrome_id)
            except Exception:
                logger.exception("Failed to delete Navidrome user %s", user.navidrome_id)

        # Cancel Stripe subscriptions
        for sub in Subscription.objects.filter(user=user, stripe_subscription_id__gt=''):
            try:
                stripe_service.cancel_subscription_immediately(sub.stripe_subscription_id)
            except Exception:
                logger.exception("Failed to cancel Stripe subscription %s", sub.stripe_subscription_id)

        try:
            email_service.send_account_deleted(user)
        except Exception:
            logger.exception("Failed to send deletion email")

        user.status = User.Status.DELETED
        user.is_active = False
        user.save(update_fields=['status', 'is_active'])
        messages.success(request, f"Utilisateur {user.username} supprimé.")
        return redirect('admin_portal:user_list')
    return redirect('admin_portal:user_detail', user_id=user_id)


# ─── Wallet Management ──────────────────────────────────────────────────────

@staff_required
def wallet_adjust(request, user_id):
    user = get_object_or_404(User, pk=user_id)
    wallet, _ = Wallet.objects.get_or_create(user=user)
    if request.method == 'POST':
        amount = request.POST.get('amount', '0')
        description = request.POST.get('description', 'Ajustement admin')
        try:
            from decimal import Decimal
            amount = Decimal(amount)
            wallet.balance += amount
            wallet.save(update_fields=['balance'])
            WalletTransaction.objects.create(
                wallet=wallet,
                type=WalletTransaction.Type.ADJUSTMENT,
                amount=amount,
                description=description,
            )
            messages.success(request, f"Portefeuille ajusté de {amount}€.")
        except Exception as e:
            messages.error(request, f"Erreur: {e}")
    return redirect('admin_portal:user_detail', user_id=user_id)


# ─── Plan Management ────────────────────────────────────────────────────────

@staff_required
def plan_list(request):
    plans = Plan.objects.all()
    return render(request, 'admin_portal/plans/list.html', {'plans': plans})


@staff_required
def plan_create(request):
    if request.method == 'POST':
        form = PlanForm(request.POST)
        if form.is_valid():
            form.save()
            messages.success(request, "Formule créée avec succès.")
            return redirect('admin_portal:plan_list')
    else:
        form = PlanForm()
    return render(request, 'admin_portal/plans/form.html', {'form': form, 'title': 'Nouvelle formule'})


@staff_required
def plan_edit(request, plan_id):
    plan = get_object_or_404(Plan, pk=plan_id)
    if request.method == 'POST':
        form = PlanForm(request.POST, instance=plan)
        if form.is_valid():
            form.save()
            messages.success(request, "Formule mise à jour.")
            return redirect('admin_portal:plan_list')
    else:
        form = PlanForm(instance=plan)
    return render(request, 'admin_portal/plans/form.html', {'form': form, 'title': f'Modifier {plan.name}'})


@staff_required
def plan_delete(request, plan_id):
    plan = get_object_or_404(Plan, pk=plan_id)
    if request.method == 'POST':
        plan.is_active = False
        plan.save(update_fields=['is_active'])
        messages.success(request, f"Formule {plan.name} désactivée.")
    return redirect('admin_portal:plan_list')


# ─── Promo Code Management ──────────────────────────────────────────────────

@staff_required
def promo_list(request):
    promos = PromoCode.objects.all()
    return render(request, 'admin_portal/promos/list.html', {'promos': promos})


@staff_required
def promo_create(request):
    if request.method == 'POST':
        form = PromoCodeForm(request.POST)
        if form.is_valid():
            form.save()
            messages.success(request, "Code promo créé.")
            return redirect('admin_portal:promo_list')
    else:
        form = PromoCodeForm()
    return render(request, 'admin_portal/promos/form.html', {'form': form, 'title': 'Nouveau code promo'})


@staff_required
def promo_edit(request, promo_id):
    promo = get_object_or_404(PromoCode, pk=promo_id)
    if request.method == 'POST':
        form = PromoCodeForm(request.POST, instance=promo)
        if form.is_valid():
            form.save()
            messages.success(request, "Code promo mis à jour.")
            return redirect('admin_portal:promo_list')
    else:
        form = PromoCodeForm(instance=promo)
    return render(request, 'admin_portal/promos/form.html', {'form': form, 'title': f'Modifier {promo.code}'})


# ─── Payment & Refund Management ────────────────────────────────────────────

@staff_required
def payment_list(request):
    payments = Payment.objects.select_related('user', 'subscription').all()
    return render(request, 'admin_portal/payments/list.html', {'payments': payments})


@staff_required
def refund_list(request):
    refunds = Refund.objects.select_related('payment', 'payment__user', 'processed_by').all()
    return render(request, 'admin_portal/refunds/list.html', {'refunds': refunds})


@staff_required
def refund_create(request, payment_id):
    payment = get_object_or_404(Payment, pk=payment_id)
    if request.method == 'POST':
        amount = request.POST.get('amount', str(payment.amount))
        reason = request.POST.get('reason', '')
        refund_to = request.POST.get('refund_to', 'original')

        from decimal import Decimal
        amount = Decimal(amount)

        refund = Refund.objects.create(
            payment=payment,
            amount=amount,
            reason=reason,
            refund_to=refund_to,
            processed_by=request.user,
        )

        try:
            if refund_to == 'original' and payment.stripe_payment_intent_id:
                stripe_refund = stripe_service.create_refund(
                    payment.stripe_payment_intent_id,
                    amount=amount if amount < payment.amount else None,
                )
                refund.stripe_refund_id = stripe_refund.id
            elif refund_to == 'wallet':
                wallet, _ = Wallet.objects.get_or_create(user=payment.user)
                wallet.balance += amount
                wallet.save(update_fields=['balance'])
                WalletTransaction.objects.create(
                    wallet=wallet,
                    type=WalletTransaction.Type.REFUND,
                    amount=amount,
                    description=f"Remboursement paiement #{str(payment.id)[:8]}",
                )

            refund.status = Refund.Status.PROCESSED
            refund.save()

            if amount >= payment.amount:
                payment.status = Payment.Status.REFUNDED
            else:
                payment.status = Payment.Status.PARTIALLY_REFUNDED
            payment.save(update_fields=['status'])

            try:
                email_service.send_refund_processed(payment.user, refund)
            except Exception:
                logger.exception("Failed to send refund email")

            messages.success(request, f"Remboursement de {amount}€ effectué.")
        except Exception as e:
            refund.status = Refund.Status.FAILED
            refund.save(update_fields=['status'])
            messages.error(request, f"Erreur lors du remboursement: {e}")

        return redirect('admin_portal:payment_list')

    return render(request, 'admin_portal/refunds/create.html', {'payment': payment})


# ─── Subscription Management ────────────────────────────────────────────────

@staff_required
def subscription_list(request):
    subscriptions = Subscription.objects.select_related('user', 'plan').all()
    return render(request, 'admin_portal/subscriptions/list.html', {'subscriptions': subscriptions})


@staff_required
def subscription_create(request):
    if request.method == 'POST':
        form = SubscriptionForm(request.POST)
        if form.is_valid():
            sub = form.save(commit=False)
            sub.status = Subscription.Status.ACTIVE
            sub.current_period_start = timezone.now()
            plan = sub.plan
            if plan.billing_cycle == Plan.BillingCycle.MONTHLY:
                from datetime import timedelta
                sub.current_period_end = timezone.now() + timedelta(days=30)
            elif plan.billing_cycle == Plan.BillingCycle.QUARTERLY:
                from datetime import timedelta
                sub.current_period_end = timezone.now() + timedelta(days=90)
            else:
                from datetime import timedelta
                sub.current_period_end = timezone.now() + timedelta(days=365)
            sub.save()
            messages.success(request, "Abonnement créé.")
            return redirect('admin_portal:subscription_list')
    else:
        form = SubscriptionForm()
    return render(request, 'admin_portal/subscriptions/form.html', {'form': form, 'title': 'Nouvel abonnement'})


# ─── Support Tickets ────────────────────────────────────────────────────────

@staff_required
def ticket_list(request):
    status_filter = request.GET.get('status', '')
    tickets = Ticket.objects.select_related('user', 'assigned_to').all()
    if status_filter:
        tickets = tickets.filter(status=status_filter)
    return render(request, 'admin_portal/tickets/list.html', {'tickets': tickets, 'status_filter': status_filter})


@staff_required
def ticket_detail(request, ticket_id):
    ticket = get_object_or_404(Ticket, pk=ticket_id)
    ticket_messages = ticket.messages.select_related('author').all()

    if request.method == 'POST':
        body = request.POST.get('body', '').strip()
        new_status = request.POST.get('status', '')

        if body:
            TicketMessage.objects.create(
                ticket=ticket,
                author=request.user,
                body=body,
                is_staff_reply=True,
            )

        if new_status and new_status != ticket.status:
            ticket.status = new_status
            if new_status in ['resolved', 'closed']:
                ticket.closed_at = timezone.now()
            ticket.save()
            messages.success(request, f"Statut du ticket mis à jour: {ticket.get_status_display()}")

        return redirect('admin_portal:ticket_detail', ticket_id=ticket_id)

    return render(request, 'admin_portal/tickets/detail.html', {
        'ticket': ticket,
        'ticket_messages': ticket_messages,
    })


# ─── SMTP & Email Templates ─────────────────────────────────────────────────

@staff_required
def smtp_config(request):
    config = SMTPConfiguration.objects.first()
    if request.method == 'POST':
        if 'test_email' in request.POST:
            test_email = request.POST.get('test_email_address', '')
            if config and test_email:
                success, message = email_service.test_smtp_config(config, test_email)
                if success:
                    messages.success(request, message)
                else:
                    messages.error(request, message)
            return redirect('admin_portal:smtp_config')

        form = SMTPConfigForm(request.POST, instance=config)
        if form.is_valid():
            form.save()
            messages.success(request, "Configuration SMTP sauvegardée.")
            return redirect('admin_portal:smtp_config')
    else:
        form = SMTPConfigForm(instance=config)
    return render(request, 'admin_portal/settings/smtp.html', {'form': form, 'config': config})


@staff_required
def email_template_list(request):
    templates = EmailTemplate.objects.all()
    return render(request, 'admin_portal/settings/email_templates.html', {'templates': templates})


@staff_required
def email_template_edit(request, template_id=None):
    template = get_object_or_404(EmailTemplate, pk=template_id) if template_id else None
    if request.method == 'POST':
        form = EmailTemplateForm(request.POST, instance=template)
        if form.is_valid():
            form.save()
            messages.success(request, "Template sauvegardé.")
            return redirect('admin_portal:email_template_list')
    else:
        form = EmailTemplateForm(instance=template)
    return render(request, 'admin_portal/settings/email_template_form.html', {
        'form': form,
        'template': template,
    })
