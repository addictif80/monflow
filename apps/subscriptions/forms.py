from django import forms

from .models import Plan, PromoCode, Subscription


class PlanForm(forms.ModelForm):
    class Meta:
        model = Plan
        fields = ['name', 'description', 'price', 'billing_cycle', 'stripe_price_id', 'max_devices', 'is_active', 'sort_order']
        widgets = {
            'name': forms.TextInput(attrs={'class': 'form-control'}),
            'description': forms.Textarea(attrs={'class': 'form-control', 'rows': 3}),
            'price': forms.NumberInput(attrs={'class': 'form-control', 'step': '0.01'}),
            'billing_cycle': forms.Select(attrs={'class': 'form-select'}),
            'stripe_price_id': forms.TextInput(attrs={'class': 'form-control'}),
            'max_devices': forms.NumberInput(attrs={'class': 'form-control'}),
            'is_active': forms.CheckboxInput(attrs={'class': 'form-check-input'}),
            'sort_order': forms.NumberInput(attrs={'class': 'form-control'}),
        }


class PromoCodeForm(forms.ModelForm):
    class Meta:
        model = PromoCode
        fields = ['code', 'discount_type', 'discount_value', 'max_uses', 'applicable_plans', 'valid_from', 'valid_until', 'is_active']
        widgets = {
            'code': forms.TextInput(attrs={'class': 'form-control'}),
            'discount_type': forms.Select(attrs={'class': 'form-select'}),
            'discount_value': forms.NumberInput(attrs={'class': 'form-control', 'step': '0.01'}),
            'max_uses': forms.NumberInput(attrs={'class': 'form-control'}),
            'applicable_plans': forms.CheckboxSelectMultiple(),
            'valid_from': forms.DateTimeInput(attrs={'class': 'form-control', 'type': 'datetime-local'}),
            'valid_until': forms.DateTimeInput(attrs={'class': 'form-control', 'type': 'datetime-local'}),
            'is_active': forms.CheckboxInput(attrs={'class': 'form-check-input'}),
        }


class SubscriptionForm(forms.ModelForm):
    class Meta:
        model = Subscription
        fields = ['user', 'plan', 'promo_code']
        widgets = {
            'user': forms.Select(attrs={'class': 'form-select'}),
            'plan': forms.Select(attrs={'class': 'form-select'}),
            'promo_code': forms.Select(attrs={'class': 'form-select'}),
        }
