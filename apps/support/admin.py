from django.contrib import admin

from .models import Ticket, TicketMessage


class TicketMessageInline(admin.TabularInline):
    model = TicketMessage
    extra = 0


@admin.register(Ticket)
class TicketAdmin(admin.ModelAdmin):
    list_display = ['subject', 'user', 'category', 'priority', 'status', 'created_at']
    list_filter = ['status', 'category', 'priority']
    inlines = [TicketMessageInline]
