from django.urls import path

from . import views

app_name = 'support'

urlpatterns = [
    path('tickets/', views.ticket_list, name='ticket_list'),
    path('tickets/create/', views.ticket_create, name='ticket_create'),
    path('tickets/<uuid:ticket_id>/', views.ticket_detail, name='ticket_detail'),
]
