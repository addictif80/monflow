from django.contrib import admin

from .models import Payment, Refund


@admin.register(Payment)
class PaymentAdmin(admin.ModelAdmin):
    list_display = ['user', 'amount', 'status', 'payment_method', 'created_at']
    list_filter = ['status', 'payment_method']


@admin.register(Refund)
class RefundAdmin(admin.ModelAdmin):
    list_display = ['payment', 'amount', 'status', 'refund_to', 'created_at']
    list_filter = ['status', 'refund_to']
