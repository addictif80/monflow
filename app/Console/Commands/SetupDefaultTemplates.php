<?php

namespace App\Console\Commands;

use App\Models\EmailTemplate;
use Illuminate\Console\Command;

class SetupDefaultTemplates extends Command
{
    protected $signature = 'setup:email-templates';
    protected $description = 'Create default email templates';

    public function handle(): void
    {
        $templates = [
            ['template_type' => 'email_verification', 'subject' => 'Confirmez votre inscription à {{ site_name }}', 'html_body' => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:20px"><h1 style="color:#6366f1">Confirmez votre email</h1><p>Bonjour {{ first_name }},</p><p>Bienvenue sur {{ site_name }} ! Pour activer votre compte <strong>{{ username }}</strong>, cliquez sur le lien ci-dessous :</p><a href="{{ verify_url }}" style="display:inline-block;background:#6366f1;color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none;margin-top:10px">Confirmer mon email</a><p style="margin-top:20px;color:#666;font-size:12px">Si vous n\'êtes pas à l\'origine de cette inscription, ignorez cet email.</p></div>'],
            ['template_type' => 'welcome', 'subject' => 'Bienvenue sur {{ site_name }} !', 'html_body' => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:20px"><h1 style="color:#6366f1">Bienvenue {{ first_name }} !</h1><p>Votre compte <strong>{{ username }}</strong> est maintenant actif sur {{ site_name }}.</p><p>Connectez-vous et choisissez votre formule pour commencer à profiter de votre musique.</p><a href="{{ site_url }}/login" style="display:inline-block;background:#6366f1;color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none;margin-top:10px">Se connecter</a></div>'],
            ['template_type' => 'payment_reminder', 'subject' => '{{ site_name }} — Rappel de paiement', 'html_body' => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:20px"><h1 style="color:#f59e0b">Rappel de paiement</h1><p>Bonjour {{ first_name }},</p><p>Votre abonnement arrive à échéance. Veuillez régulariser votre paiement pour continuer à profiter de {{ site_name }}.</p><a href="{{ site_url }}/portal/plans" style="display:inline-block;background:#f59e0b;color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none;margin-top:10px">Renouveler</a></div>'],
            ['template_type' => 'password_reset', 'subject' => '{{ site_name }} — Réinitialisation de mot de passe', 'html_body' => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:20px"><h1 style="color:#6366f1">Réinitialisation de mot de passe</h1><p>Bonjour {{ first_name }},</p><p>Cliquez sur le lien ci-dessous pour réinitialiser votre mot de passe :</p><a href="{{ reset_url }}" style="display:inline-block;background:#6366f1;color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none;margin-top:10px">Réinitialiser</a><p style="margin-top:20px;color:#666;font-size:12px">Si vous n\'avez pas demandé cette réinitialisation, ignorez cet email.</p></div>'],
            ['template_type' => 'account_suspended', 'subject' => '{{ site_name }} — Compte suspendu', 'html_body' => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:20px"><h1 style="color:#ef4444">Compte suspendu</h1><p>Bonjour {{ first_name }},</p><p>Votre compte <strong>{{ username }}</strong> a été suspendu en raison d\'un impayé. Votre accès à la musique est temporairement désactivé.</p><p>Régularisez votre situation pour retrouver votre accès avec votre mot de passe habituel.</p><a href="{{ site_url }}/login" style="display:inline-block;background:#ef4444;color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none;margin-top:10px">Régulariser</a></div>'],
            ['template_type' => 'account_deleted', 'subject' => '{{ site_name }} — Compte supprimé', 'html_body' => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:20px"><h1 style="color:#ef4444">Compte supprimé</h1><p>Bonjour {{ first_name }},</p><p>Votre compte <strong>{{ username }}</strong> a été définitivement supprimé de {{ site_name }} suite à un impayé prolongé.</p><p>Si vous souhaitez revenir, vous pouvez créer un nouveau compte.</p></div>'],
            ['template_type' => 'gift_received', 'subject' => 'Vous avez reçu un cadeau {{ site_name }} !', 'html_body' => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:20px"><h1 style="color:#10b981">Un cadeau pour vous !</h1><p>Quelqu\'un vous a offert un abonnement <strong>{{ plan }}</strong> sur {{ site_name }} !</p><p>Connectez-vous pour profiter de votre musique.</p><a href="{{ site_url }}/login" style="display:inline-block;background:#10b981;color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none;margin-top:10px">Accéder</a></div>'],
            ['template_type' => 'refund_processed', 'subject' => '{{ site_name }} — Remboursement effectué', 'html_body' => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:20px"><h1 style="color:#6366f1">Remboursement effectué</h1><p>Bonjour {{ first_name }},</p><p>Votre remboursement a été traité avec succès. Le montant sera crédité sous quelques jours.</p></div>'],
            ['template_type' => 'subscription_renewed', 'subject' => '{{ site_name }} — Abonnement renouvelé', 'html_body' => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:20px"><h1 style="color:#10b981">Abonnement renouvelé</h1><p>Bonjour {{ first_name }},</p><p>Votre abonnement {{ site_name }} a été renouvelé avec succès. Bonne écoute !</p></div>'],
        ];

        foreach ($templates as $tpl) {
            EmailTemplate::updateOrCreate(
                ['template_type' => $tpl['template_type']],
                array_merge($tpl, ['is_active' => true])
            );
        }

        $this->info('Default email templates created/updated.');
    }
}
