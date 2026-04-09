import uuid

from django.contrib.auth.models import AbstractUser
from django.db import models


class User(AbstractUser):
    """Custom user model with Navidrome integration and wallet."""

    class Status(models.TextChoices):
        ACTIVE = 'active', 'Actif'
        SUSPENDED = 'suspended', 'Suspendu'
        DELETED = 'deleted', 'Supprimé'

    id = models.UUIDField(primary_key=True, default=uuid.uuid4, editable=False)
    navidrome_id = models.CharField(max_length=255, blank=True, default='')
    phone = models.CharField(max_length=20, blank=True, default='')
    status = models.CharField(max_length=20, choices=Status.choices, default=Status.ACTIVE)
    stripe_customer_id = models.CharField(max_length=255, blank=True, default='')
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    class Meta:
        db_table = 'users'
        ordering = ['-created_at']

    def __str__(self):
        return self.email or self.username

    @property
    def is_active_subscriber(self):
        return self.status == self.Status.ACTIVE and self.subscriptions.filter(
            status='active'
        ).exists()


class Wallet(models.Model):
    """User wallet for prepaid balance."""

    id = models.UUIDField(primary_key=True, default=uuid.uuid4, editable=False)
    user = models.OneToOneField(User, on_delete=models.CASCADE, related_name='wallet')
    balance = models.DecimalField(max_digits=10, decimal_places=2, default=0)
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    class Meta:
        db_table = 'wallets'

    def __str__(self):
        return f"Wallet {self.user} — {self.balance}€"


class WalletTransaction(models.Model):
    """Transaction history for wallets."""

    class Type(models.TextChoices):
        TOPUP = 'topup', 'Rechargement'
        PAYMENT = 'payment', 'Paiement'
        REFUND = 'refund', 'Remboursement'
        GIFT = 'gift', 'Cadeau'
        ADJUSTMENT = 'adjustment', 'Ajustement'

    id = models.UUIDField(primary_key=True, default=uuid.uuid4, editable=False)
    wallet = models.ForeignKey(Wallet, on_delete=models.CASCADE, related_name='transactions')
    type = models.CharField(max_length=20, choices=Type.choices)
    amount = models.DecimalField(max_digits=10, decimal_places=2)
    description = models.CharField(max_length=500, default='')
    stripe_payment_intent_id = models.CharField(max_length=255, blank=True, default='')
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        db_table = 'wallet_transactions'
        ordering = ['-created_at']

    def __str__(self):
        return f"{self.get_type_display()} {self.amount}€ — {self.wallet.user}"


class UserDevice(models.Model):
    """Track connected devices/sessions for a user."""

    id = models.UUIDField(primary_key=True, default=uuid.uuid4, editable=False)
    user = models.ForeignKey(User, on_delete=models.CASCADE, related_name='devices')
    device_name = models.CharField(max_length=255)
    device_type = models.CharField(max_length=100, blank=True, default='')
    ip_address = models.GenericIPAddressField(null=True, blank=True)
    user_agent = models.TextField(blank=True, default='')
    session_key = models.CharField(max_length=255, blank=True, default='')
    last_active = models.DateTimeField(auto_now=True)
    is_active = models.BooleanField(default=True)
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        db_table = 'user_devices'
        ordering = ['-last_active']

    def __str__(self):
        return f"{self.device_name} — {self.user}"


class SMTPConfiguration(models.Model):
    """SMTP server configuration managed from admin panel."""

    id = models.UUIDField(primary_key=True, default=uuid.uuid4, editable=False)
    name = models.CharField(max_length=255, default='Default')
    host = models.CharField(max_length=255)
    port = models.IntegerField(default=587)
    username = models.CharField(max_length=255, blank=True, default='')
    password = models.CharField(max_length=255, blank=True, default='')
    use_tls = models.BooleanField(default=True)
    use_ssl = models.BooleanField(default=False)
    from_email = models.EmailField()
    from_name = models.CharField(max_length=255, default='MonFlow')
    is_active = models.BooleanField(default=True)
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    class Meta:
        db_table = 'smtp_configurations'

    def __str__(self):
        return f"{self.name} ({self.host}:{self.port})"


class EmailTemplate(models.Model):
    """HTML email templates for automated emails."""

    class TemplateType(models.TextChoices):
        WELCOME = 'welcome', 'Bienvenue'
        PAYMENT_REMINDER = 'payment_reminder', 'Relance paiement'
        PASSWORD_RESET = 'password_reset', 'Réinitialisation mot de passe'
        ACCOUNT_SUSPENDED = 'account_suspended', 'Compte suspendu'
        ACCOUNT_DELETED = 'account_deleted', 'Compte supprimé'
        GIFT_RECEIVED = 'gift_received', 'Abonnement offert reçu'
        PAYMENT_SUCCESS = 'payment_success', 'Paiement réussi'
        REFUND_PROCESSED = 'refund_processed', 'Remboursement effectué'

    id = models.UUIDField(primary_key=True, default=uuid.uuid4, editable=False)
    template_type = models.CharField(max_length=50, choices=TemplateType.choices, unique=True)
    subject = models.CharField(max_length=255)
    html_body = models.TextField()
    is_active = models.BooleanField(default=True)
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    class Meta:
        db_table = 'email_templates'

    def __str__(self):
        return f"{self.get_template_type_display()}"
