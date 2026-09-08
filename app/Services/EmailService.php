<?php

namespace App\Services;

use App\Models\{SmtpConfiguration, EmailTemplate, EmailLog, User};
use App\Jobs\SendEmailJob;
use Illuminate\Support\Facades\{Log, URL};

class EmailService
{
    private function getSmtp(): SmtpConfiguration
    {
        return SmtpConfiguration::where('is_active', true)->firstOrFail();
    }

    private function render(string $template, array $ctx): string
    {
        foreach ($ctx as $k => $v) {
            $template = str_replace("{{ {$k} }}", (string) $v, $template);
            $template = str_replace("{{{$k}}}", (string) $v, $template);
        }
        return $template;
    }

    private function send(SmtpConfiguration $smtp, string $to, string $subject, string $html, ?string $type = null, array $attachments = []): void
    {
        $transport = new \Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport(
            $smtp->host, $smtp->port, $smtp->use_tls
        );
        if ($smtp->username) {
            $transport->setUsername($smtp->username);
            $transport->setPassword($smtp->password);
        }
        $mailer = new \Symfony\Component\Mailer\Mailer($transport);
        $email = (new \Symfony\Component\Mime\Email())
            ->from(new \Symfony\Component\Mime\Address($smtp->from_email, $smtp->from_name))
            ->to($to)
            ->subject($subject)
            ->html($html);

        foreach ($attachments as $attachment) {
            $email->attachFromPath($attachment['path'], $attachment['name'] ?? null, $attachment['mime'] ?? null);
        }

        try {
            $mailer->send($email);
            Log::info("Email sent to {$to}: {$subject}");
            EmailLog::create(['to' => $to, 'subject' => $subject, 'template_type' => $type, 'html_body' => $html, 'status' => 'sent']);
        } catch (\Throwable $e) {
            EmailLog::create(['to' => $to, 'subject' => $subject, 'template_type' => $type, 'html_body' => $html, 'status' => 'failed', 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function sendTemplate(string $type, string $toEmail, array $ctx = []): void
    {
        SendEmailJob::dispatch($type, $toEmail, $ctx);
    }

    public function sendTemplateNow(string $type, string $toEmail, array $ctx = []): void
    {
        try {
            $smtp = $this->getSmtp();
            $tpl = EmailTemplate::where('template_type', $type)->where('is_active', true)->firstOrFail();
            $ctx['site_name'] = config('app.name');
            $ctx['site_url'] = config('app.url');
            $subject = $this->render($tpl->subject, $ctx);
            $body = $this->render($tpl->html_body, $ctx);
            $this->send($smtp, $toEmail, $subject, $body, $type);
        } catch (\Exception $e) {
            Log::error("Failed to send email [{$type}] to {$toEmail}: {$e->getMessage()}");
        }
    }

    /**
     * Envoi synchrone avec pièce jointe (rapport URSSAF) : contrairement à
     * sendTemplateNow(), les erreurs remontent pour que l'appelant puisse
     * marquer l'envoi en échec plutôt que le considérer silencieusement fait.
     */
    public function sendUrssafReport(string $toEmail, string $periodLabel, float $total, int $count, string $pdfPath): void
    {
        $smtp = $this->getSmtp();
        $tpl = EmailTemplate::where('template_type', 'urssaf_report')->where('is_active', true)->firstOrFail();
        $ctx = [
            'period' => $periodLabel,
            'total' => number_format($total, 2, ',', ' '),
            'count' => $count,
            'site_name' => config('app.name'),
            'site_url' => config('app.url'),
        ];
        $subject = $this->render($tpl->subject, $ctx);
        $body = $this->render($tpl->html_body, $ctx);
        $this->send($smtp, $toEmail, $subject, $body, 'urssaf_report', [
            ['path' => $pdfPath, 'name' => "declaration-urssaf-{$periodLabel}.pdf", 'mime' => 'application/pdf'],
        ]);
    }

    public function sendVerification(User $u, string $url): void { $this->sendTemplate('email_verification', $u->email, ['username' => $u->username, 'first_name' => $u->first_name, 'verify_url' => $url]); }
    public function sendWelcome(User $u): void { $this->sendTemplate('welcome', $u->email, ['username' => $u->username, 'first_name' => $u->first_name]); }
    public function sendPaymentReminder(User $u, int $days): void { $this->sendTemplate('payment_reminder', $u->email, ['username' => $u->username, 'first_name' => $u->first_name, 'days_overdue' => $days]); }
    public function sendPasswordReset(User $u, string $url): void { $this->sendTemplate('password_reset', $u->email, ['username' => $u->username, 'first_name' => $u->first_name, 'reset_url' => $url]); }
    public function sendSuspended(User $u): void { $this->sendTemplate('account_suspended', $u->email, ['username' => $u->username, 'first_name' => $u->first_name]); }
    public function sendDeleted(User $u): void
    {
        // Lien signé (30 jours) permettant à l'utilisateur de libérer lui-même
        // son ancienne adresse email pour se réinscrire, sans avoir à contacter
        // le support ni à s'authentifier (le compte est supprimé).
        $resubscribeUrl = URL::temporarySignedRoute('resubscribe', now()->addDays(30), ['id' => $u->id]);
        $this->sendTemplate('account_deleted', $u->email, ['username' => $u->username, 'first_name' => $u->first_name, 'resubscribe_url' => $resubscribeUrl]);
    }
    public function sendDeletedRecoverable(User $u, float $fee): void { $this->sendTemplate('account_deleted_recoverable', $u->email, ['username' => $u->username, 'first_name' => $u->first_name, 'fee' => number_format($fee, 2, ',', ' ')]); }
    public function sendDeletionWarning(User $u, int $daysLeft, \DateTimeInterface $deletionDate): void { $this->sendTemplate('deletion_warning', $u->email, ['username' => $u->username, 'first_name' => $u->first_name, 'days_left' => $daysLeft, 'deletion_date' => $deletionDate->format('d/m/Y')]); }
    public function sendGiftReceived(string $email, string $plan): void { $this->sendTemplate('gift_received', $email, ['plan' => $plan]); }
    public function sendRefund(User $u): void { $this->sendTemplate('refund_processed', $u->email, ['username' => $u->username, 'first_name' => $u->first_name]); }
    public function sendRenewalReminder(User $u, \App\Models\Plan $plan, float $price, bool $promoEnding = false): void { $this->sendTemplate('renewal_reminder', $u->email, ['username' => $u->username, 'first_name' => $u->first_name, 'plan_name' => $plan->name, 'price' => $price, 'promo_ending' => $promoEnding]); }

    public function sendNewsletterNow(User $u, string $subject, string $htmlBody): void
    {
        $smtp = $this->getSmtp();
        $ctx = ['username' => $u->username, 'first_name' => $u->first_name, 'site_name' => config('app.name'), 'site_url' => config('app.url'), 'sujet' => $subject];
        $renderedSubject = $this->render($subject, $ctx);
        $renderedBody = $this->render($htmlBody, $ctx);
        $unsubLink = config('app.url') . '/portal/profile';
        $renderedBody = str_replace('</body>', "<div style=\"text-align:center;padding:16px;font-size:11px;color:#a1a1aa\"><a href=\"{$unsubLink}\" style=\"color:#6366f1\">Se désabonner de la newsletter</a></div></body>", $renderedBody);
        $this->send($smtp, $u->email, $renderedSubject, $renderedBody, 'newsletter');
    }

    public function testSmtp(SmtpConfiguration $smtp, string $testEmail): array
    {
        try {
            $this->send($smtp, $testEmail, 'Test SMTP — ' . config('app.name'), '<h1>Test OK</h1><p>La configuration SMTP fonctionne.</p>', 'test_smtp');
            return ['success' => true, 'message' => 'Email envoyé avec succès'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
