from django import forms
from django.contrib.auth.forms import AuthenticationForm, SetPasswordForm

from .models import User, SMTPConfiguration, EmailTemplate


class LoginForm(AuthenticationForm):
    username = forms.CharField(
        label="Nom d'utilisateur ou email",
        widget=forms.TextInput(attrs={'class': 'form-control', 'placeholder': "Nom d'utilisateur"}),
    )
    password = forms.CharField(
        label="Mot de passe",
        widget=forms.PasswordInput(attrs={'class': 'form-control', 'placeholder': 'Mot de passe'}),
    )


class RegisterForm(forms.ModelForm):
    password = forms.CharField(
        label="Mot de passe",
        widget=forms.PasswordInput(attrs={'class': 'form-control'}),
    )
    password_confirm = forms.CharField(
        label="Confirmer le mot de passe",
        widget=forms.PasswordInput(attrs={'class': 'form-control'}),
    )

    class Meta:
        model = User
        fields = ['username', 'email', 'first_name', 'last_name']
        widgets = {
            'username': forms.TextInput(attrs={'class': 'form-control'}),
            'email': forms.EmailInput(attrs={'class': 'form-control'}),
            'first_name': forms.TextInput(attrs={'class': 'form-control'}),
            'last_name': forms.TextInput(attrs={'class': 'form-control'}),
        }

    def clean(self):
        cleaned_data = super().clean()
        password = cleaned_data.get('password')
        password_confirm = cleaned_data.get('password_confirm')
        if password and password_confirm and password != password_confirm:
            raise forms.ValidationError("Les mots de passe ne correspondent pas.")
        return cleaned_data


class ChangePasswordForm(forms.Form):
    current_password = forms.CharField(
        label="Mot de passe actuel",
        widget=forms.PasswordInput(attrs={'class': 'form-control'}),
    )
    new_password = forms.CharField(
        label="Nouveau mot de passe",
        widget=forms.PasswordInput(attrs={'class': 'form-control'}),
    )
    new_password_confirm = forms.CharField(
        label="Confirmer le nouveau mot de passe",
        widget=forms.PasswordInput(attrs={'class': 'form-control'}),
    )

    def clean(self):
        cleaned_data = super().clean()
        new_password = cleaned_data.get('new_password')
        confirm = cleaned_data.get('new_password_confirm')
        if new_password and confirm and new_password != confirm:
            raise forms.ValidationError("Les nouveaux mots de passe ne correspondent pas.")
        return cleaned_data


class ProfileForm(forms.ModelForm):
    class Meta:
        model = User
        fields = ['first_name', 'last_name', 'email', 'phone']
        widgets = {
            'first_name': forms.TextInput(attrs={'class': 'form-control'}),
            'last_name': forms.TextInput(attrs={'class': 'form-control'}),
            'email': forms.EmailInput(attrs={'class': 'form-control'}),
            'phone': forms.TextInput(attrs={'class': 'form-control'}),
        }


class PasswordResetRequestForm(forms.Form):
    email = forms.EmailField(
        label="Adresse email",
        widget=forms.EmailInput(attrs={'class': 'form-control', 'placeholder': 'votre@email.com'}),
    )


class PasswordResetForm(SetPasswordForm):
    new_password1 = forms.CharField(
        label="Nouveau mot de passe",
        widget=forms.PasswordInput(attrs={'class': 'form-control'}),
    )
    new_password2 = forms.CharField(
        label="Confirmer le nouveau mot de passe",
        widget=forms.PasswordInput(attrs={'class': 'form-control'}),
    )


class AdminUserForm(forms.ModelForm):
    """Form for admin to create/edit users."""

    password = forms.CharField(
        label="Mot de passe",
        widget=forms.PasswordInput(attrs={'class': 'form-control'}),
        required=False,
    )

    class Meta:
        model = User
        fields = ['username', 'email', 'first_name', 'last_name', 'phone', 'status', 'is_staff']
        widgets = {
            'username': forms.TextInput(attrs={'class': 'form-control'}),
            'email': forms.EmailInput(attrs={'class': 'form-control'}),
            'first_name': forms.TextInput(attrs={'class': 'form-control'}),
            'last_name': forms.TextInput(attrs={'class': 'form-control'}),
            'phone': forms.TextInput(attrs={'class': 'form-control'}),
            'status': forms.Select(attrs={'class': 'form-select'}),
            'is_staff': forms.CheckboxInput(attrs={'class': 'form-check-input'}),
        }


class SMTPConfigForm(forms.ModelForm):
    password = forms.CharField(
        widget=forms.PasswordInput(attrs={'class': 'form-control'}),
        required=False,
    )

    class Meta:
        model = SMTPConfiguration
        fields = ['name', 'host', 'port', 'username', 'password', 'use_tls', 'use_ssl', 'from_email', 'from_name', 'is_active']
        widgets = {
            'name': forms.TextInput(attrs={'class': 'form-control'}),
            'host': forms.TextInput(attrs={'class': 'form-control'}),
            'port': forms.NumberInput(attrs={'class': 'form-control'}),
            'username': forms.TextInput(attrs={'class': 'form-control'}),
            'from_email': forms.EmailInput(attrs={'class': 'form-control'}),
            'from_name': forms.TextInput(attrs={'class': 'form-control'}),
            'use_tls': forms.CheckboxInput(attrs={'class': 'form-check-input'}),
            'use_ssl': forms.CheckboxInput(attrs={'class': 'form-check-input'}),
            'is_active': forms.CheckboxInput(attrs={'class': 'form-check-input'}),
        }


class EmailTemplateForm(forms.ModelForm):
    class Meta:
        model = EmailTemplate
        fields = ['template_type', 'subject', 'html_body', 'is_active']
        widgets = {
            'template_type': forms.Select(attrs={'class': 'form-select'}),
            'subject': forms.TextInput(attrs={'class': 'form-control'}),
            'html_body': forms.Textarea(attrs={'class': 'form-control', 'rows': 15}),
            'is_active': forms.CheckboxInput(attrs={'class': 'form-check-input'}),
        }
