<?php

namespace App\Console\Commands;

use App\Models\EmailTemplate;
use Illuminate\Console\Command;

class SetupDefaultTemplates extends Command
{
    protected $signature = 'setup:email-templates';
    protected $description = 'Create default email templates';

    private function wrap(string $content, string $accentColor = '#6366f1'): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f5;padding:40px 20px">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%">
  <!-- Header -->
  <tr><td style="background:#18181b;border-radius:12px 12px 0 0;padding:32px 40px;text-align:center">
    <img src="https://client.monflow.fr/icons/icon-192.png" alt="MonFlow" width="64" height="64" style="display:block;margin:0 auto;border-radius:12px">
  </td></tr>
  <!-- Body -->
  <tr><td style="background:#ffffff;padding:40px 40px 32px;border-left:1px solid #e4e4e7;border-right:1px solid #e4e4e7">
    {$content}
  </td></tr>
  <!-- Footer -->
  <tr><td style="background:#fafafa;border-radius:0 0 12px 12px;padding:24px 40px;border:1px solid #e4e4e7;border-top:none;text-align:center">
    <p style="margin:0;font-size:12px;color:#a1a1aa;line-height:1.5">{{ site_name }} &mdash; Votre musique, votre espace<br>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
  </td></tr>
</table>
</td></tr>
</table>
</body>
</html>
HTML;
    }

    private function btn(string $url, string $label, string $color = '#6366f1'): string
    {
        return "<table cellpadding=\"0\" cellspacing=\"0\" style=\"margin:24px 0\"><tr><td style=\"background:{$color};border-radius:8px;padding:14px 28px\"><a href=\"{$url}\" style=\"color:#ffffff;text-decoration:none;font-weight:600;font-size:14px;display:inline-block\">{$label}</a></td></tr></table>";
    }

    private function heading(string $text, string $color = '#18181b'): string
    {
        return "<h1 style=\"margin:0 0 20px;font-size:22px;font-weight:700;color:{$color};line-height:1.3\">{$text}</h1>";
    }

    private function p(string $text): string
    {
        return "<p style=\"margin:0 0 16px;font-size:15px;color:#3f3f46;line-height:1.6\">{$text}</p>";
    }

    private function note(string $text): string
    {
        return "<p style=\"margin:24px 0 0;font-size:12px;color:#a1a1aa;line-height:1.5\">{$text}</p>";
    }

    public function handle(): void
    {
        $templates = [
            [
                'template_type' => 'email_verification',
                'subject' => 'Confirmez votre adresse email — {{ site_name }}',
                'html_body' => $this->wrap(
                    $this->heading('Confirmez votre adresse email')
                    . $this->p('Bonjour {{ first_name }},')
                    . $this->p('Merci de vous être inscrit sur {{ site_name }}. Pour activer votre compte <strong>{{ username }}</strong>, veuillez confirmer votre adresse email en cliquant sur le bouton ci-dessous.')
                    . $this->btn('{{ verify_url }}', 'Confirmer mon email')
                    . $this->note('Si vous n\'êtes pas à l\'origine de cette inscription, vous pouvez ignorer cet email en toute sécurité.')
                ),
            ],
            [
                'template_type' => 'welcome',
                'subject' => 'Bienvenue sur {{ site_name }}, {{ first_name }} !',
                'html_body' => $this->wrap(
                    $this->heading('Bienvenue sur {{ site_name }} !')
                    . $this->p('Bonjour {{ first_name }},')
                    . $this->p('Votre compte <strong>{{ username }}</strong> est maintenant actif. Choisissez une formule pour accéder à votre espace musical et profiter de toutes les fonctionnalités.')
                    . $this->btn('{{ site_url }}/portal/plans', 'Découvrir les formules')
                    . $this->p('Si vous avez la moindre question, n\'hésitez pas à contacter notre support.')
                ),
            ],
            [
                'template_type' => 'payment_reminder',
                'subject' => 'Rappel : paiement en attente — {{ site_name }}',
                'html_body' => $this->wrap(
                    $this->heading('Paiement en attente', '#d97706')
                    . $this->p('Bonjour {{ first_name }},')
                    . $this->p('Votre abonnement {{ site_name }} est en retard de <strong>{{ days_overdue }} jour(s)</strong>. Pour éviter la suspension de votre compte et conserver vos playlists, veuillez régulariser votre situation.')
                    . $this->btn('{{ site_url }}/portal/plans', 'Régulariser mon paiement', '#d97706')
                    . $this->note('Sans action de votre part, votre accès sera suspendu prochainement.')
                ),
            ],
            [
                'template_type' => 'password_reset',
                'subject' => 'Réinitialisation de votre mot de passe — {{ site_name }}',
                'html_body' => $this->wrap(
                    $this->heading('Réinitialisation de mot de passe')
                    . $this->p('Bonjour {{ first_name }},')
                    . $this->p('Vous avez demandé la réinitialisation de votre mot de passe. Cliquez sur le bouton ci-dessous pour en choisir un nouveau.')
                    . $this->btn('{{ reset_url }}', 'Réinitialiser mon mot de passe')
                    . $this->note('Ce lien expire dans 60 minutes. Si vous n\'avez pas fait cette demande, ignorez cet email — votre mot de passe actuel reste inchangé.')
                ),
            ],
            [
                'template_type' => 'account_suspended',
                'subject' => 'Votre compte {{ site_name }} a été suspendu',
                'html_body' => $this->wrap(
                    $this->heading('Compte suspendu', '#dc2626')
                    . $this->p('Bonjour {{ first_name }},')
                    . $this->p('Votre compte <strong>{{ username }}</strong> a été suspendu en raison d\'un impayé. Votre accès à la musique est temporairement désactivé.')
                    . $this->p('Rassurez-vous : vos playlists et préférences sont conservées. Régularisez votre situation pour retrouver votre accès avec votre mot de passe habituel.')
                    . $this->btn('{{ site_url }}/login', 'Régulariser ma situation', '#dc2626')
                ),
            ],
            [
                'template_type' => 'deletion_warning',
                'subject' => 'Votre compte {{ site_name }} sera supprimé dans {{ days_left }} jours',
                'html_body' => $this->wrap(
                    $this->heading('Suppression imminente de votre compte', '#d97706')
                    . $this->p('Bonjour {{ first_name }},')
                    . $this->p('Votre compte <strong>{{ username }}</strong> est suspendu depuis un impayé prolongé. Sans régularisation, il sera <strong>définitivement supprimé le {{ deletion_date }}</strong> (dans {{ days_left }} jours) : vos playlists, votre historique et vos préférences seront alors perdus.')
                    . $this->p('Régularisez votre situation avant cette date pour conserver vos données et retrouver votre accès avec votre mot de passe habituel.')
                    . $this->btn('{{ site_url }}/login', 'Régulariser ma situation', '#d97706')
                    . $this->note('Passé ce délai, cette action sera irréversible.')
                ),
            ],
            [
                'template_type' => 'account_deleted',
                'subject' => 'Votre compte {{ site_name }} a été supprimé',
                'html_body' => $this->wrap(
                    $this->heading('Compte supprimé', '#dc2626')
                    . $this->p('Bonjour {{ first_name }},')
                    . $this->p('Votre compte <strong>{{ username }}</strong> a été définitivement supprimé de {{ site_name }} suite à un impayé prolongé. Vos données et playlists ne sont plus accessibles.')
                    . $this->p('Si vous souhaitez revenir, vous pouvez créer un nouveau compte à tout moment.')
                    . $this->btn('{{ site_url }}/register', 'Créer un nouveau compte', '#6366f1')
                ),
            ],
            [
                'template_type' => 'gift_received',
                'subject' => 'Vous avez reçu un cadeau {{ site_name }} !',
                'html_body' => $this->wrap(
                    $this->heading('Un cadeau pour vous !', '#059669')
                    . $this->p('Bonne nouvelle !')
                    . $this->p('Quelqu\'un vous a offert un abonnement <strong>{{ plan }}</strong> sur {{ site_name }}. Votre accès est déjà activé — connectez-vous pour découvrir votre espace musical.')
                    . $this->btn('{{ site_url }}/login', 'Accéder à mon espace', '#059669')
                    . $this->p('Bonne écoute !')
                ),
            ],
            [
                'template_type' => 'refund_processed',
                'subject' => 'Remboursement effectué — {{ site_name }}',
                'html_body' => $this->wrap(
                    $this->heading('Remboursement confirmé')
                    . $this->p('Bonjour {{ first_name }},')
                    . $this->p('Votre remboursement a été traité avec succès. Le montant sera crédité sur votre moyen de paiement sous 5 à 10 jours ouvrés selon votre banque.')
                    . $this->p('Si vous avez des questions, n\'hésitez pas à contacter notre support.')
                ),
            ],
            [
                'template_type' => 'subscription_renewed',
                'subject' => 'Abonnement renouvelé — {{ site_name }}',
                'html_body' => $this->wrap(
                    $this->heading('Abonnement renouvelé', '#059669')
                    . $this->p('Bonjour {{ first_name }},')
                    . $this->p('Votre abonnement {{ site_name }} a été renouvelé avec succès. Votre accès continue sans interruption.')
                    . $this->btn('{{ site_url }}/portal', 'Accéder à mon espace', '#059669')
                    . $this->p('Bonne écoute !')
                ),
            ],
            [
                'template_type' => 'renewal_reminder',
                'subject' => 'Votre abonnement sera renouvelé dans 7 jours — {{ site_name }}',
                'html_body' => $this->wrap(
                    $this->heading('Renouvellement dans 7 jours')
                    . $this->p('Bonjour {{ first_name }},')
                    . $this->p('Votre abonnement <strong>{{ plan_name }}</strong> sera automatiquement renouvelé dans 7 jours au tarif de <strong>{{ price }} €</strong>.')
                    . $this->p('Si vous souhaitez modifier ou résilier votre abonnement avant le renouvellement, vous pouvez le faire depuis votre espace client.')
                    . $this->btn('{{ site_url }}/portal', 'Gérer mon abonnement')
                    . $this->note('Ce message est envoyé à titre informatif pour vous permettre de gérer votre abonnement en toute sérénité.')
                ),
            ],
            [
                'template_type' => 'newsletter_layout',
                'subject' => '',
                'html_body' => <<<'LAYOUT'
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f5;padding:40px 20px">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%">
  <tr><td style="background:#18181b;border-radius:12px 12px 0 0;padding:32px 40px;text-align:center">
    <img src="https://client.monflow.fr/icons/icon-192.png" alt="MonFlow" width="64" height="64" style="display:block;margin:0 auto;border-radius:12px">
  </td></tr>
  <tr><td style="background:#ffffff;padding:40px 40px 32px;border-left:1px solid #e4e4e7;border-right:1px solid #e4e4e7">
    {{ content }}
  </td></tr>
  <tr><td style="background:#fafafa;border-radius:0 0 12px 12px;padding:24px 40px;border:1px solid #e4e4e7;border-top:none;text-align:center">
    <p style="margin:0;font-size:12px;color:#a1a1aa;line-height:1.5">{{ site_name }} &mdash; Votre musique, votre espace<br>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
  </td></tr>
</table>
</td></tr>
</table>
</body>
</html>
LAYOUT,
            ],
        ];

        foreach ($templates as $tpl) {
            EmailTemplate::updateOrCreate(
                ['template_type' => $tpl['template_type']],
                array_merge($tpl, ['is_active' => true, 'subject' => $tpl['subject'] ?? ''])
            );
        }

        $this->info('Default email templates created/updated (' . count($templates) . ' templates).');
    }
}
