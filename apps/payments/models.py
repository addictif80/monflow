import uuid

from django.db import models


class Payment(models.Model):
    """Payment record."""

    class Status(models.TextChoices):
        PENDING = 'pending', 'En attente'
        SUCCEEDED = 'succeeded', 'Réussi'
        FAILED = 'failed', 'Échoué'
        REFUNDED = 'refunded', 'Remboursé'
        PARTIALLY_REFUNDED = 'partially_refunded', 'Partiellement remboursé'

    class PaymentMethod(models.TextChoices):
        STRIPE = 'stripe', 'Carte bancaire (Stripe)'
        WALLET = 'wallet', 'Portefeuille'
        MIXED = 'mixed', 'Mixte (Wallet + Stripe)'

    id = models.UUIDField(primary_key=True, default=uuid.uuid4, editable=False)
    user = models.ForeignKey('accounts.User', on_delete=models.CASCADE, related_name='payments')
    subscription = models.ForeignKey(
        'subscriptions.Subscription', null=True, blank=True, on_delete=models.SET_NULL, related_name='payments'
    )
    amount = models.DecimalField(max_digits=10, decimal_places=2)
    wallet_amount = models.DecimalField(max_digits=10, decimal_places=2, default=0)
    stripe_amount = models.DecimalField(max_digits=10, decimal_places=2, default=0)
    status = models.CharField(max_length=30, choices=Status.choices, default=Status.PENDING)
    payment_method = models.CharField(max_length=20, choices=PaymentMethod.choices, default=PaymentMethod.STRIPE)
    stripe_payment_intent_id = models.CharField(max_length=255, blank=True, default='')
    stripe_invoice_id = models.CharField(max_length=255, blank=True, default='')
    description = models.CharField(max_length=500, default='')
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    class Meta:
        db_table = 'payments'
        ordering = ['-created_at']

    def __str__(self):
        return f"Payment {self.amount}€ — {self.user} ({self.get_status_display()})"


class Refund(models.Model):
    """Refund record linked to a payment."""

    class Status(models.TextChoices):
        PENDING = 'pending', 'En attente'
        PROCESSED = 'processed', 'Traité'
        FAILED = 'failed', 'Échoué'

    class RefundTo(models.TextChoices):
        ORIGINAL = 'original', 'Moyen de paiement original'
        WALLET = 'wallet', 'Portefeuille'

    id = models.UUIDField(primary_key=True, default=uuid.uuid4, editable=False)
    payment = models.ForeignKey(Payment, on_delete=models.CASCADE, related_name='refunds')
    amount = models.DecimalField(max_digits=10, decimal_places=2)
    reason = models.TextField(blank=True, default='')
    status = models.CharField(max_length=20, choices=Status.choices, default=Status.PENDING)
    refund_to = models.CharField(max_length=20, choices=RefundTo.choices, default=RefundTo.ORIGINAL)
    stripe_refund_id = models.CharField(max_length=255, blank=True, default='')
    processed_by = models.ForeignKey(
        'accounts.User', null=True, blank=True, on_delete=models.SET_NULL, related_name='processed_refunds'
    )
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    class Meta:
        db_table = 'refunds'
        ordering = ['-created_at']

    def __str__(self):
        return f"Refund {self.amount}€ — Payment {self.payment_id}"
