from django.urls import path

from . import views

app_name = 'payments'

urlpatterns = [
    path('success/', views.payment_success, name='payment_success'),
    path('wallet-success/', views.wallet_success, name='wallet_success'),
    path('gift-success/', views.gift_success, name='gift_success'),
    path('webhook/stripe/', views.stripe_webhook, name='stripe_webhook'),
]
