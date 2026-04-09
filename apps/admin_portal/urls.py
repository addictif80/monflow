from django.urls import path

from . import views

app_name = 'admin_portal'

urlpatterns = [
    # Dashboard
    path('', views.dashboard, name='dashboard'),

    # Users
    path('users/', views.user_list, name='user_list'),
    path('users/create/', views.user_create, name='user_create'),
    path('users/<uuid:user_id>/', views.user_detail, name='user_detail'),
    path('users/<uuid:user_id>/edit/', views.user_edit, name='user_edit'),
    path('users/<uuid:user_id>/suspend/', views.user_suspend, name='user_suspend'),
    path('users/<uuid:user_id>/reactivate/', views.user_reactivate, name='user_reactivate'),
    path('users/<uuid:user_id>/delete/', views.user_delete, name='user_delete'),
    path('users/<uuid:user_id>/wallet-adjust/', views.wallet_adjust, name='wallet_adjust'),

    # Plans
    path('plans/', views.plan_list, name='plan_list'),
    path('plans/create/', views.plan_create, name='plan_create'),
    path('plans/<uuid:plan_id>/edit/', views.plan_edit, name='plan_edit'),
    path('plans/<uuid:plan_id>/delete/', views.plan_delete, name='plan_delete'),

    # Promo codes
    path('promos/', views.promo_list, name='promo_list'),
    path('promos/create/', views.promo_create, name='promo_create'),
    path('promos/<uuid:promo_id>/edit/', views.promo_edit, name='promo_edit'),

    # Payments & Refunds
    path('payments/', views.payment_list, name='payment_list'),
    path('refunds/', views.refund_list, name='refund_list'),
    path('payments/<uuid:payment_id>/refund/', views.refund_create, name='refund_create'),

    # Subscriptions
    path('subscriptions/', views.subscription_list, name='subscription_list'),
    path('subscriptions/create/', views.subscription_create, name='subscription_create'),

    # Tickets
    path('tickets/', views.ticket_list, name='ticket_list'),
    path('tickets/<uuid:ticket_id>/', views.ticket_detail, name='ticket_detail'),

    # Settings
    path('settings/smtp/', views.smtp_config, name='smtp_config'),
    path('settings/email-templates/', views.email_template_list, name='email_template_list'),
    path('settings/email-templates/new/', views.email_template_edit, name='email_template_create'),
    path('settings/email-templates/<uuid:template_id>/', views.email_template_edit, name='email_template_edit'),
]
