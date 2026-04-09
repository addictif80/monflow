"""Email service with HTML templates and custom SMTP configuration."""

import logging
import smtplib
from email.mime.multipart import MIMEMultipart
from email.mime.text import MIMEText

from django.template import Template, Context

logger = logging.getLogger(__name__)


class EmailService:
    """Service for sending emails using DB-configured SMTP and templates."""

    def _get_smtp_config(self):
        """Get the active SMTP configuration from the database."""
        from apps.accounts.models import SMTPConfiguration
        config = SMTPConfiguration.objects.filter(is_active=True).first()
        if not config:
            raise ValueError("Aucune configuration SMTP active trouvée")
        return config

    def _get_template(self, template_type):
        """Get an email template by type."""
        from apps.accounts.models import EmailTemplate
        template = EmailTemplate.objects.filter(
            template_type=template_type, is_active=True
        ).first()
        if not template:
            raise ValueError(f"Template email '{template_type}' introuvable ou inactif")
        return template

    def _render_template(self, template_str, context_dict):
        """Render a Django template string with the given context."""
        template = Template(template_str)
        context = Context(context_dict)
        return template.render(context)

    def _send_email(self, smtp_config, to_email, subject, html_body):
        """Send an email using the given SMTP configuration."""
        msg = MIMEMultipart('alternative')
        msg['Subject'] = subject
        msg['From'] = f"{smtp_config.from_name} <{smtp_config.from_email}>"
        msg['To'] = to_email

        html_part = MIMEText(html_body, 'html', 'utf-8')
        msg.attach(html_part)

        if smtp_config.use_ssl:
            server = smtplib.SMTP_SSL(smtp_config.host, smtp_config.port, timeout=10)
        else:
            server = smtplib.SMTP(smtp_config.host, smtp_config.port, timeout=10)

        try:
            if smtp_config.use_tls and not smtp_config.use_ssl:
                server.starttls()
            if smtp_config.username:
                server.login(smtp_config.username, smtp_config.password)
            server.sendmail(smtp_config.from_email, [to_email], msg.as_string())
            logger.info("Email sent to %s: %s", to_email, subject)
        finally:
            server.quit()

    def send_template_email(self, template_type, to_email, context_dict=None):
        """Send an email using a stored template."""
        smtp_config = self._get_smtp_config()
        template = self._get_template(template_type)

        context_dict = context_dict or {}
        from django.conf import settings
        context_dict.setdefault('site_name', settings.SITE_NAME)
        context_dict.setdefault('site_url', settings.SITE_URL)

        subject = self._render_template(template.subject, context_dict)
        html_body = self._render_template(template.html_body, context_dict)

        self._send_email(smtp_config, to_email, subject, html_body)

    def send_welcome(self, user):
        """Send welcome email to a new user."""
        self.send_template_email('welcome', user.email, {
            'user': user,
            'username': user.username,
            'first_name': user.first_name,
        })

    def send_payment_reminder(self, user, subscription, days_overdue):
        """Send payment reminder email."""
        self.send_template_email('payment_reminder', user.email, {
            'user': user,
            'username': user.username,
            'first_name': user.first_name,
            'subscription': subscription,
            'days_overdue': days_overdue,
        })

    def send_password_reset(self, user, reset_url):
        """Send password reset email."""
        self.send_template_email('password_reset', user.email, {
            'user': user,
            'username': user.username,
            'first_name': user.first_name,
            'reset_url': reset_url,
        })

    def send_account_suspended(self, user):
        """Send account suspended notification."""
        self.send_template_email('account_suspended', user.email, {
            'user': user,
            'username': user.username,
            'first_name': user.first_name,
        })

    def send_account_deleted(self, user):
        """Send account deleted notification."""
        self.send_template_email('account_deleted', user.email, {
            'user': user,
            'username': user.username,
            'first_name': user.first_name,
        })

    def send_gift_received(self, recipient_email, gifted_by, plan):
        """Send gift received notification."""
        self.send_template_email('gift_received', recipient_email, {
            'gifted_by': gifted_by,
            'plan': plan,
        })

    def send_payment_success(self, user, payment):
        """Send payment success notification."""
        self.send_template_email('payment_success', user.email, {
            'user': user,
            'username': user.username,
            'first_name': user.first_name,
            'payment': payment,
        })

    def send_refund_processed(self, user, refund):
        """Send refund processed notification."""
        self.send_template_email('refund_processed', user.email, {
            'user': user,
            'username': user.username,
            'first_name': user.first_name,
            'refund': refund,
        })

    def test_smtp_config(self, smtp_config, test_email):
        """Test SMTP configuration by sending a test email."""
        try:
            self._send_email(
                smtp_config,
                test_email,
                'Test de configuration SMTP — MonFlow',
                '<h1>Test SMTP</h1><p>La configuration SMTP fonctionne correctement.</p>',
            )
            return True, "Email de test envoyé avec succès"
        except Exception as e:
            return False, f"Erreur: {e}"


email_service = EmailService()
