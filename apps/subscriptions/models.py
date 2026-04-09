import uuid

from django.db import models
from django.utils import timezone


class Plan(models.Model):
    """Subscription plan / product catalog."""

    class BillingCycle(models.TextChoices):
        MONTHLY = 'monthly', 'Mensuel'
        QUARTERLY = 'quarterly', 'Trimestriel'
        YEARLY = 'yearly', 'Annuel'

    id = models.UUIDField(primary_key=True, default=uuid.uuid4, editable=False)
    name = models.CharField(max_length=255)
    description = models.TextField(blank=True, default='')
    price = models.DecimalField(max_digits=10, decimal_places=2)
    billing_cycle = models.CharField(max_length=20, choices=BillingCycle.choices, default=BillingCycle.MONTHLY)
    stripe_price_id = models.CharField(max_length=255, blank=True, default='')
    max_devices = models.IntegerField(default=3)
    is_active = models.BooleanField(default=True)
    sort_order = models.IntegerField(default=0)
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    class Meta:
        db_table = 'plans'
        ordering = ['sort_order', 'price']

    def __str__(self):
        return f"{self.name} — {self.price}€/{self.get_billing_cycle_display()}"


class PromoCode(models.Model):
    """Promotional codes for discounts."""

    class DiscountType(models.TextChoices):
        PERCENTAGE = 'percentage', 'Pourcentage'
        FIXED = 'fixed', 'Montant fixe'
        FREE_MONTHS = 'free_months', 'Mois gratuits'

    id = models.UUIDField(primary_key=True, default=uuid.uuid4, editable=False)
    code = models.CharField(max_length=50, unique=True)
    discount_type = models.CharField(max_length=20, choices=DiscountType.choices)
    discount_value = models.DecimalField(max_digits=10, decimal_places=2)
    max_uses = models.IntegerField(default=0, help_text="0 = illimité")
    current_uses = models.IntegerField(default=0)
    applicable_plans = models.ManyToManyField(Plan, blank=True, related_name='promo_codes')
    valid_from = models.DateTimeField(default=timezone.now)
    valid_until = models.DateTimeField(null=True, blank=True)
    is_active = models.BooleanField(default=True)
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        db_table = 'promo_codes'

    def __str__(self):
        return self.code

    @property
    def is_valid(self):
        now = timezone.now()
        if not self.is_active:
            return False
        if self.max_uses > 0 and self.current_uses >= self.max_uses:
            return False
        if self.valid_until and now > self.valid_until:
            return False
        if now < self.valid_from:
            return False
        return True


class Subscription(models.Model):
    """User subscription linking a user to a plan."""

    class Status(models.TextChoices):
        ACTIVE = 'active', 'Actif'
        SUSPENDED = 'suspended', 'Suspendu'
        CANCELLED = 'cancelled', 'Annulé'
        EXPIRED = 'expired', 'Expiré'
        PENDING = 'pending', 'En attente'

    id = models.UUIDField(primary_key=True, default=uuid.uuid4, editable=False)
    user = models.ForeignKey('accounts.User', on_delete=models.CASCADE, related_name='subscriptions')
    plan = models.ForeignKey(Plan, on_delete=models.PROTECT, related_name='subscriptions')
    status = models.CharField(max_length=20, choices=Status.choices, default=Status.PENDING)
    stripe_subscription_id = models.CharField(max_length=255, blank=True, default='')
    promo_code = models.ForeignKey(PromoCode, null=True, blank=True, on_delete=models.SET_NULL)
    current_period_start = models.DateTimeField(null=True, blank=True)
    current_period_end = models.DateTimeField(null=True, blank=True)
    cancelled_at = models.DateTimeField(null=True, blank=True)
    is_gift = models.BooleanField(default=False)
    gifted_by = models.ForeignKey(
        'accounts.User', null=True, blank=True, on_delete=models.SET_NULL, related_name='gifts_given'
    )
    gift_recipient_email = models.EmailField(blank=True, default='')
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    class Meta:
        db_table = 'subscriptions'
        ordering = ['-created_at']

    def __str__(self):
        return f"{self.user} — {self.plan.name} ({self.get_status_display()})"

    @property
    def is_overdue(self):
        if self.current_period_end and self.status == self.Status.ACTIVE:
            return timezone.now() > self.current_period_end
        return False

    @property
    def days_overdue(self):
        if self.is_overdue:
            return (timezone.now() - self.current_period_end).days
        return 0
