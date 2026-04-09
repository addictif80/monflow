from django.urls import path

from . import views

app_name = 'customer_portal'

urlpatterns = [
    path('', views.dashboard, name='dashboard'),
    path('profile/', views.profile, name='profile'),
    path('password/', views.change_password, name='change_password'),
    path('plans/', views.subscription_plans, name='subscription_plans'),
    path('subscribe/<uuid:plan_id>/', views.subscribe, name='subscribe'),
    path('cancel-subscription/', views.cancel_subscription, name='cancel_subscription'),
    path('wallet/', views.wallet_view, name='wallet'),
    path('wallet/topup/', views.wallet_topup, name='wallet_topup'),
    path('gift/', views.gift_subscription, name='gift_subscription'),
    path('devices/', views.devices, name='devices'),
    path('devices/<uuid:device_id>/revoke/', views.revoke_device, name='revoke_device'),
    path('payments/', views.payment_history, name='payment_history'),
]
