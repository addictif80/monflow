from django.contrib import admin
from django.contrib.auth.admin import UserAdmin as BaseUserAdmin

from .models import User, Wallet, WalletTransaction, UserDevice, SMTPConfiguration, EmailTemplate


@admin.register(User)
class UserAdmin(BaseUserAdmin):
    list_display = ['username', 'email', 'status', 'navidrome_id', 'created_at']
    list_filter = ['status', 'is_staff']
    fieldsets = BaseUserAdmin.fieldsets + (
        ('MonFlow', {'fields': ('navidrome_id', 'phone', 'status', 'stripe_customer_id')}),
    )


@admin.register(Wallet)
class WalletAdmin(admin.ModelAdmin):
    list_display = ['user', 'balance', 'updated_at']


@admin.register(WalletTransaction)
class WalletTransactionAdmin(admin.ModelAdmin):
    list_display = ['wallet', 'type', 'amount', 'created_at']
    list_filter = ['type']


@admin.register(UserDevice)
class UserDeviceAdmin(admin.ModelAdmin):
    list_display = ['user', 'device_name', 'device_type', 'is_active', 'last_active']


@admin.register(SMTPConfiguration)
class SMTPConfigurationAdmin(admin.ModelAdmin):
    list_display = ['name', 'host', 'port', 'is_active']


@admin.register(EmailTemplate)
class EmailTemplateAdmin(admin.ModelAdmin):
    list_display = ['template_type', 'subject', 'is_active', 'updated_at']
