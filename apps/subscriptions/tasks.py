"""Celery tasks for automated subscription management.

- suspend users at payment due + 7 days
- delete users at payment due + 30 days
- send payment reminders
"""

import logging

from celery import shared_task
from django.conf import settings
from django.utils import timezone

logger = logging.getLogger(__name__)


@shared_task
def check_overdue_subscriptions():
    """Check all active subscriptions for overdue payments.

    - J+7: Suspend user
    - J+30: Delete user
    - J+1, J+3, J+5: Send reminders
    """
    from apps.accounts.models import User
    from apps.subscriptions.models import Subscription
    from services.email_service import email_service
    from services.navidrome import navidrome_client
    from services.stripe_service import stripe_service

    now = timezone.now()
    suspend_days = settings.PAYMENT_SUSPEND_DELAY_DAYS
    delete_days = settings.PAYMENT_DELETE_DELAY_DAYS

    # Check active subscriptions past due
    overdue_active = Subscription.objects.filter(
        status=Subscription.Status.ACTIVE,
        current_period_end__lt=now,
    ).select_related('user', 'plan')

    for sub in overdue_active:
        days = (now - sub.current_period_end).days

        if days >= delete_days:
            # Delete user (J+30)
            _delete_user(sub, email_service, navidrome_client, stripe_service)
        elif days >= suspend_days:
            # Suspend user (J+7)
            _suspend_user(sub, email_service)
        elif days in (1, 3, 5):
            # Send reminder
            try:
                email_service.send_payment_reminder(sub.user, sub, days)
            except Exception:
                logger.exception("Failed to send payment reminder to %s", sub.user.email)

    # Check suspended subscriptions for deletion at J+30
    overdue_suspended = Subscription.objects.filter(
        status=Subscription.Status.SUSPENDED,
        current_period_end__lt=now,
    ).select_related('user', 'plan')

    for sub in overdue_suspended:
        days = (now - sub.current_period_end).days
        if days >= delete_days:
            _delete_user(sub, email_service, navidrome_client, stripe_service)

    logger.info("Overdue subscription check completed")


def _suspend_user(sub, email_service):
    """Suspend a user due to overdue payment.

    Changes the Navidrome password to a random value to lock out the user
    while preserving their data (playlists, favorites, play counts).
    """
    from apps.accounts.models import User
    from services.navidrome import navidrome_client

    user = sub.user
    if user.status != User.Status.SUSPENDED:
        user.status = User.Status.SUSPENDED
        user.is_active = False
        user.save(update_fields=['status', 'is_active'])

        # Lock out from Navidrome by randomizing password
        if user.navidrome_id:
            try:
                navidrome_client.suspend_user(user.navidrome_id)
            except Exception:
                logger.exception("Failed to suspend Navidrome user %s", user.navidrome_id)

    sub.status = sub.Status.SUSPENDED
    sub.save(update_fields=['status'])

    try:
        email_service.send_account_suspended(user)
    except Exception:
        logger.exception("Failed to send suspension email to %s", user.email)

    logger.info("Suspended user %s due to overdue payment", user.username)


def _delete_user(sub, email_service, navidrome_client, stripe_service):
    """Delete a user due to prolonged overdue payment."""
    from apps.accounts.models import User

    user = sub.user

    # Send email before deletion
    try:
        email_service.send_account_deleted(user)
    except Exception:
        logger.exception("Failed to send deletion email to %s", user.email)

    # Delete from Navidrome
    if user.navidrome_id:
        try:
            navidrome_client.delete_user(user.navidrome_id)
        except Exception:
            logger.exception("Failed to delete Navidrome user %s", user.navidrome_id)

    # Cancel Stripe subscription
    if sub.stripe_subscription_id:
        try:
            stripe_service.cancel_subscription_immediately(sub.stripe_subscription_id)
        except Exception:
            logger.exception("Failed to cancel Stripe subscription %s", sub.stripe_subscription_id)

    # Mark as deleted
    user.status = User.Status.DELETED
    user.is_active = False
    user.save(update_fields=['status', 'is_active'])

    sub.status = sub.Status.CANCELLED
    sub.save(update_fields=['status'])

    logger.info("Deleted user %s due to prolonged overdue payment", user.username)


@shared_task
def send_payment_reminders():
    """Send payment reminders for upcoming due dates (J-3 before expiry)."""
    from apps.subscriptions.models import Subscription
    from services.email_service import email_service
    from datetime import timedelta

    now = timezone.now()
    three_days_from_now = now + timedelta(days=3)

    upcoming = Subscription.objects.filter(
        status=Subscription.Status.ACTIVE,
        current_period_end__date=three_days_from_now.date(),
    ).select_related('user', 'plan')

    for sub in upcoming:
        try:
            email_service.send_payment_reminder(sub.user, sub, -3)
        except Exception:
            logger.exception("Failed to send upcoming payment reminder to %s", sub.user.email)

    logger.info("Upcoming payment reminders sent for %d subscriptions", upcoming.count())
