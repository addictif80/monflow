from django import forms

from .models import Ticket


class TicketForm(forms.ModelForm):
    message = forms.CharField(
        label="Description",
        widget=forms.Textarea(attrs={'class': 'form-control', 'rows': 5}),
    )

    class Meta:
        model = Ticket
        fields = ['subject', 'category', 'priority']
        widgets = {
            'subject': forms.TextInput(attrs={'class': 'form-control'}),
            'category': forms.Select(attrs={'class': 'form-select'}),
            'priority': forms.Select(attrs={'class': 'form-select'}),
        }


class TicketMessageForm(forms.Form):
    body = forms.CharField(
        label="Message",
        widget=forms.Textarea(attrs={'class': 'form-control', 'rows': 3}),
    )
