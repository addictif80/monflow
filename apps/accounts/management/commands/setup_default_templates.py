"""Management command to create default email templates."""

from django.core.management.base import BaseCommand

from apps.accounts.models import EmailTemplate


DEFAULT_TEMPLATES = {
    'welcome': {
        'subject': 'Bienvenue sur {{ site_name }} !',
        'html_body': '''<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="text-align: center; padding: 20px; background: linear-gradient(135deg, #1e1b4b, #4338ca); border-radius: 12px; margin-bottom: 20px;">
        <h1 style="color: white; margin: 0;">{{ site_name }}</h1>
    </div>
    <h2>Bienvenue {{ first_name|default:username }} !</h2>
    <p>Votre compte a été créé avec succès. Vous pouvez maintenant accéder à notre catalogue musical.</p>
    <p>Votre nom d'utilisateur : <strong>{{ username }}</strong></p>
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ site_url }}/login/" style="background: #4338ca; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">Se connecter</a>
    </div>
    <p style="color: #666; font-size: 14px;">L'équipe {{ site_name }}</p>
</body>
</html>''',
    },
    'payment_reminder': {
        'subject': 'Rappel de paiement — {{ site_name }}',
        'html_body': '''<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="text-align: center; padding: 20px; background: linear-gradient(135deg, #1e1b4b, #4338ca); border-radius: 12px; margin-bottom: 20px;">
        <h1 style="color: white; margin: 0;">{{ site_name }}</h1>
    </div>
    <h2>Rappel de paiement</h2>
    <p>Bonjour {{ first_name|default:username }},</p>
    <p>Votre paiement est en retard de <strong>{{ days_overdue }} jour(s)</strong>. Veuillez régulariser votre situation pour conserver l'accès à votre musique.</p>
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ site_url }}/portal/" style="background: #4338ca; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">Régulariser</a>
    </div>
    <p style="color: #dc2626; font-size: 14px;"><strong>Attention :</strong> Votre compte sera suspendu après 7 jours de retard et supprimé après 30 jours.</p>
</body>
</html>''',
    },
    'password_reset': {
        'subject': 'Réinitialisation de mot de passe — {{ site_name }}',
        'html_body': '''<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="text-align: center; padding: 20px; background: linear-gradient(135deg, #1e1b4b, #4338ca); border-radius: 12px; margin-bottom: 20px;">
        <h1 style="color: white; margin: 0;">{{ site_name }}</h1>
    </div>
    <h2>Réinitialisation de mot de passe</h2>
    <p>Bonjour {{ first_name|default:username }},</p>
    <p>Vous avez demandé la réinitialisation de votre mot de passe. Cliquez sur le bouton ci-dessous :</p>
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ reset_url }}" style="background: #4338ca; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">Réinitialiser mon mot de passe</a>
    </div>
    <p style="color: #666; font-size: 14px;">Si vous n'avez pas fait cette demande, ignorez cet email.</p>
</body>
</html>''',
    },
    'account_suspended': {
        'subject': 'Compte suspendu — {{ site_name }}',
        'html_body': '''<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="text-align: center; padding: 20px; background: linear-gradient(135deg, #92400e, #dc2626); border-radius: 12px; margin-bottom: 20px;">
        <h1 style="color: white; margin: 0;">{{ site_name }}</h1>
    </div>
    <h2>Votre compte a été suspendu</h2>
    <p>Bonjour {{ first_name|default:username }},</p>
    <p>Votre compte a été suspendu en raison d'un retard de paiement. Votre accès à la musique est temporairement désactivé.</p>
    <p>Pour réactiver votre compte, veuillez régulariser votre paiement.</p>
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ site_url }}/portal/" style="background: #dc2626; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">Régulariser mon paiement</a>
    </div>
    <p style="color: #dc2626; font-size: 14px;"><strong>Attention :</strong> Votre compte sera définitivement supprimé après 30 jours de retard.</p>
</body>
</html>''',
    },
    'account_deleted': {
        'subject': 'Compte supprimé — {{ site_name }}',
        'html_body': '''<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="text-align: center; padding: 20px; background: #1e293b; border-radius: 12px; margin-bottom: 20px;">
        <h1 style="color: white; margin: 0;">{{ site_name }}</h1>
    </div>
    <h2>Votre compte a été supprimé</h2>
    <p>Bonjour {{ first_name|default:username }},</p>
    <p>Votre compte a été supprimé suite à un retard de paiement prolongé. Toutes vos données ont été effacées.</p>
    <p>Si vous souhaitez revenir, vous pouvez créer un nouveau compte à tout moment.</p>
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ site_url }}/register/" style="background: #4338ca; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">Créer un nouveau compte</a>
    </div>
</body>
</html>''',
    },
    'gift_received': {
        'subject': 'On vous a offert un abonnement {{ site_name }} !',
        'html_body': '''<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="text-align: center; padding: 20px; background: linear-gradient(135deg, #1e1b4b, #4338ca); border-radius: 12px; margin-bottom: 20px;">
        <h1 style="color: white; margin: 0;">{{ site_name }}</h1>
    </div>
    <div style="text-align: center; font-size: 48px; margin: 20px 0;">🎁</div>
    <h2 style="text-align: center;">Vous avez reçu un cadeau !</h2>
    <p>Quelqu'un vous a offert un abonnement <strong>{{ plan }}</strong> sur {{ site_name }}.</p>
    <p>Connectez-vous pour profiter de l'accès à toute notre bibliothèque musicale.</p>
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ site_url }}/login/" style="background: #4338ca; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">Accéder à ma musique</a>
    </div>
</body>
</html>''',
    },
    'payment_success': {
        'subject': 'Paiement confirmé — {{ site_name }}',
        'html_body': '''<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="text-align: center; padding: 20px; background: linear-gradient(135deg, #1e1b4b, #4338ca); border-radius: 12px; margin-bottom: 20px;">
        <h1 style="color: white; margin: 0;">{{ site_name }}</h1>
    </div>
    <h2>Paiement confirmé ✓</h2>
    <p>Bonjour {{ first_name|default:username }},</p>
    <p>Votre paiement a été traité avec succès. Merci de votre confiance !</p>
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ site_url }}/portal/" style="background: #4338ca; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">Mon espace</a>
    </div>
</body>
</html>''',
    },
    'refund_processed': {
        'subject': 'Remboursement effectué — {{ site_name }}',
        'html_body': '''<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="text-align: center; padding: 20px; background: linear-gradient(135deg, #1e1b4b, #4338ca); border-radius: 12px; margin-bottom: 20px;">
        <h1 style="color: white; margin: 0;">{{ site_name }}</h1>
    </div>
    <h2>Remboursement effectué</h2>
    <p>Bonjour {{ first_name|default:username }},</p>
    <p>Un remboursement a été effectué sur votre compte. Le montant apparaîtra sur votre relevé sous quelques jours.</p>
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ site_url }}/portal/payments/" style="background: #4338ca; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">Voir mes paiements</a>
    </div>
</body>
</html>''',
    },
}


class Command(BaseCommand):
    help = 'Create default email templates if they do not exist'

    def handle(self, *args, **options):
        created_count = 0
        for template_type, data in DEFAULT_TEMPLATES.items():
            _, created = EmailTemplate.objects.get_or_create(
                template_type=template_type,
                defaults={
                    'subject': data['subject'],
                    'html_body': data['html_body'],
                },
            )
            if created:
                created_count += 1
                self.stdout.write(self.style.SUCCESS(f'Created template: {template_type}'))
            else:
                self.stdout.write(f'Template already exists: {template_type}')

        self.stdout.write(self.style.SUCCESS(f'Done. {created_count} templates created.'))
