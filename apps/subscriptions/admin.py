from django.contrib import admin

from .models import Plan, PromoCode, Subscription


@admin.register(Plan)
class PlanAdmin(admin.ModelAdmin):
    list_display = ['name', 'price', 'billing_cycle', 'is_active', 'sort_order']
    list_filter = ['billing_cycle', 'is_active']


@admin.register(PromoCode)
class PromoCodeAdmin(admin.ModelAdmin):
    list_display = ['code', 'discount_type', 'discount_value', 'current_uses', 'max_uses', 'is_active']
    list_filter = ['discount_type', 'is_active']


@admin.register(Subscription)
class SubscriptionAdmin(admin.ModelAdmin):
    list_display = ['user', 'plan', 'status', 'current_period_end', 'is_gift']
    list_filter = ['status', 'is_gift']
