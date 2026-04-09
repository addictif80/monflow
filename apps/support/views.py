from django.contrib import messages
from django.contrib.auth.decorators import login_required
from django.shortcuts import get_object_or_404, redirect, render

from .forms import TicketForm, TicketMessageForm
from .models import Ticket, TicketMessage


@login_required
def ticket_list(request):
    tickets = Ticket.objects.filter(user=request.user)
    return render(request, 'customer_portal/tickets/list.html', {'tickets': tickets})


@login_required
def ticket_create(request):
    if request.method == 'POST':
        form = TicketForm(request.POST)
        if form.is_valid():
            ticket = form.save(commit=False)
            ticket.user = request.user
            ticket.save()

            # Create initial message
            body = request.POST.get('message', '')
            if body:
                TicketMessage.objects.create(
                    ticket=ticket,
                    author=request.user,
                    body=body,
                )

            messages.success(request, "Ticket créé avec succès.")
            return redirect('support:ticket_detail', ticket_id=ticket.id)
    else:
        form = TicketForm()
    return render(request, 'customer_portal/tickets/create.html', {'form': form})


@login_required
def ticket_detail(request, ticket_id):
    ticket = get_object_or_404(Ticket, pk=ticket_id, user=request.user)
    ticket_messages = ticket.messages.select_related('author').all()

    if request.method == 'POST':
        body = request.POST.get('body', '').strip()
        if body:
            TicketMessage.objects.create(
                ticket=ticket,
                author=request.user,
                body=body,
                is_staff_reply=False,
            )
            # Reopen if it was waiting for customer
            if ticket.status == Ticket.Status.WAITING_CUSTOMER:
                ticket.status = Ticket.Status.IN_PROGRESS
                ticket.save(update_fields=['status'])
            messages.success(request, "Message envoyé.")
        return redirect('support:ticket_detail', ticket_id=ticket_id)

    return render(request, 'customer_portal/tickets/detail.html', {
        'ticket': ticket,
        'ticket_messages': ticket_messages,
    })
