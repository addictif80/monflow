import logging
import uuid

from django.conf import settings
from django.contrib import messages
from django.contrib.auth import login, logout
from django.contrib.auth.tokens import default_token_generator
from django.shortcuts import redirect, render
from django.utils.encoding import force_bytes, force_str
from django.utils.http import urlsafe_base64_decode, urlsafe_base64_encode

from services.email_service import email_service
from services.navidrome import navidrome_client

from .forms import LoginForm, RegisterForm, PasswordResetRequestForm, PasswordResetForm
from .models import User

logger = logging.getLogger(__name__)


def login_view(request):
    if request.user.is_authenticated:
        if request.user.is_staff:
            return redirect('admin_portal:dashboard')
        return redirect('customer_portal:dashboard')

    if request.method == 'POST':
        form = LoginForm(request, data=request.POST)
        if form.is_valid():
            user = form.get_user()
            if user.status == User.Status.SUSPENDED:
                messages.error(request, "Votre compte est suspendu. Veuillez régulariser votre paiement.")
                return render(request, 'accounts/login.html', {'form': form})
            if user.status == User.Status.DELETED:
                messages.error(request, "Ce compte a été supprimé.")
                return render(request, 'accounts/login.html', {'form': form})
            login(request, user)
            if user.is_staff:
                return redirect('admin_portal:dashboard')
            return redirect('customer_portal:dashboard')
    else:
        form = LoginForm()

    return render(request, 'accounts/login.html', {'form': form})


def logout_view(request):
    logout(request)
    return redirect('accounts:login')


def register_view(request):
    if request.user.is_authenticated:
        return redirect('customer_portal:dashboard')

    if request.method == 'POST':
        form = RegisterForm(request.POST)
        if form.is_valid():
            user = form.save(commit=False)
            raw_password = form.cleaned_data['password']
            user.set_password(raw_password)
            user.save()

            # Create user in Navidrome
            try:
                nd_user = navidrome_client.create_user(
                    username=user.username,
                    password=raw_password,
                    name=f"{user.first_name} {user.last_name}".strip(),
                    email=user.email,
                )
                user.navidrome_id = nd_user.get('id', '')
                user.save(update_fields=['navidrome_id'])
            except Exception:
                logger.exception("Failed to create Navidrome user for %s", user.username)

            # Send welcome email
            try:
                email_service.send_welcome(user)
            except Exception:
                logger.exception("Failed to send welcome email to %s", user.email)

            login(request, user)
            messages.success(request, "Bienvenue ! Votre compte a été créé avec succès.")
            return redirect('customer_portal:dashboard')
    else:
        form = RegisterForm()

    return render(request, 'accounts/register.html', {'form': form})


def password_reset_request_view(request):
    if request.method == 'POST':
        form = PasswordResetRequestForm(request.POST)
        if form.is_valid():
            email = form.cleaned_data['email']
            try:
                user = User.objects.get(email=email)
                uid = urlsafe_base64_encode(force_bytes(user.pk))
                token = default_token_generator.make_token(user)
                reset_url = f"{settings.SITE_URL}/password-reset/{uid}/{token}/"
                try:
                    email_service.send_password_reset(user, reset_url)
                except Exception:
                    logger.exception("Failed to send password reset email to %s", email)
            except User.DoesNotExist:
                pass  # Don't reveal whether email exists
            messages.info(request, "Si un compte existe avec cet email, un lien de réinitialisation a été envoyé.")
            return redirect('accounts:login')
    else:
        form = PasswordResetRequestForm()

    return render(request, 'accounts/password_reset_request.html', {'form': form})


def password_reset_confirm_view(request, uidb64, token):
    try:
        uid = force_str(urlsafe_base64_decode(uidb64))
        user = User.objects.get(pk=uid)
    except (TypeError, ValueError, OverflowError, User.DoesNotExist):
        user = None

    if user is not None and default_token_generator.check_token(user, token):
        if request.method == 'POST':
            form = PasswordResetForm(user, request.POST)
            if form.is_valid():
                form.save()
                raw_password = form.cleaned_data['new_password1']

                # Sync password to Navidrome
                if user.navidrome_id:
                    try:
                        navidrome_client.change_password(user.navidrome_id, raw_password)
                    except Exception:
                        logger.exception("Failed to sync password to Navidrome for %s", user.username)

                messages.success(request, "Votre mot de passe a été réinitialisé avec succès.")
                return redirect('accounts:login')
        else:
            form = PasswordResetForm(user)
        return render(request, 'accounts/password_reset_confirm.html', {'form': form})
    else:
        messages.error(request, "Le lien de réinitialisation est invalide ou a expiré.")
        return redirect('accounts:password_reset_request')
