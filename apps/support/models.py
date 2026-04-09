import uuid

from django.db import models


class Ticket(models.Model):
    """Support ticket."""

    class Status(models.TextChoices):
        OPEN = 'open', 'Ouvert'
        IN_PROGRESS = 'in_progress', 'En cours'
        WAITING_CUSTOMER = 'waiting_customer', 'En attente du client'
        RESOLVED = 'resolved', 'Résolu'
        CLOSED = 'closed', 'Fermé'

    class Priority(models.TextChoices):
        LOW = 'low', 'Basse'
        MEDIUM = 'medium', 'Moyenne'
        HIGH = 'high', 'Haute'
        URGENT = 'urgent', 'Urgente'

    class Category(models.TextChoices):
        BILLING = 'billing', 'Facturation'
        TECHNICAL = 'technical', 'Technique'
        ACCOUNT = 'account', 'Compte'
        OTHER = 'other', 'Autre'

    id = models.UUIDField(primary_key=True, default=uuid.uuid4, editable=False)
    user = models.ForeignKey('accounts.User', on_delete=models.CASCADE, related_name='tickets')
    subject = models.CharField(max_length=255)
    category = models.CharField(max_length=20, choices=Category.choices, default=Category.OTHER)
    priority = models.CharField(max_length=20, choices=Priority.choices, default=Priority.MEDIUM)
    status = models.CharField(max_length=20, choices=Status.choices, default=Status.OPEN)
    assigned_to = models.ForeignKey(
        'accounts.User', null=True, blank=True, on_delete=models.SET_NULL, related_name='assigned_tickets'
    )
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)
    closed_at = models.DateTimeField(null=True, blank=True)

    class Meta:
        db_table = 'tickets'
        ordering = ['-created_at']

    def __str__(self):
        return f"#{str(self.id)[:8]} — {self.subject}"


class TicketMessage(models.Model):
    """Message in a support ticket."""

    id = models.UUIDField(primary_key=True, default=uuid.uuid4, editable=False)
    ticket = models.ForeignKey(Ticket, on_delete=models.CASCADE, related_name='messages')
    author = models.ForeignKey('accounts.User', on_delete=models.CASCADE)
    body = models.TextField()
    is_staff_reply = models.BooleanField(default=False)
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        db_table = 'ticket_messages'
        ordering = ['created_at']

    def __str__(self):
        return f"Message on {self.ticket} by {self.author}"
