from django.contrib import admin
from django.urls import include, path

urlpatterns = [
    path('django-admin/', admin.site.urls),
    path('admin/', include('apps.admin_portal.urls', namespace='admin_portal')),
    path('portal/', include('apps.customer_portal.urls', namespace='customer_portal')),
    path('payments/', include('apps.payments.urls', namespace='payments')),
    path('support/', include('apps.support.urls', namespace='support')),
    path('', include('apps.accounts.urls', namespace='accounts')),
]
