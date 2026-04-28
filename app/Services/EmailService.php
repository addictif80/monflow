<?php

namespace App\Services;

use App\Models\{SmtpConfiguration, EmailTemplate, User};
use App\Jobs\SendEmailJob;
use Illuminate\Support\Facades\Log;

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

    private function send(SmtpConfiguration $smtp, string $to, string $subject, string $html): void
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
        $mailer->send($email);
        Log::info("Email sent to {$to}: {$subject}");
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
            $this->send($smtp, $toEmail, $subject, $body);
        } catch (\Exception $e) {
            Log::error("Failed to send email [{$type}] to {$toEmail}: {$e->getMessage()}");
        }
    }

    public function sendVerification(User $u, string $url): void { $this->sendTemplate('email_verification', $u->email, ['username' => $u->username, 'first_name' => $u->first_name, 'verify_url' => $url]); }
    public function sendWelcome(User $u): void { $this->sendTemplate('welcome', $u->email, ['username' => $u->username, 'first_name' => $u->first_name]); }
    public function sendPaymentReminder(User $u, int $days): void { $this->sendTemplate('payment_reminder', $u->email, ['username' => $u->username, 'first_name' => $u->first_name, 'days_overdue' => $days]); }
    public function sendPasswordReset(User $u, string $url): void { $this->sendTemplate('password_reset', $u->email, ['username' => $u->username, 'first_name' => $u->first_name, 'reset_url' => $url]); }
    public function sendSuspended(User $u): void { $this->sendTemplate('account_suspended', $u->email, ['username' => $u->username, 'first_name' => $u->first_name]); }
    public function sendDeleted(User $u): void { $this->sendTemplate('account_deleted', $u->email, ['username' => $u->username, 'first_name' => $u->first_name]); }
    public function sendGiftReceived(string $email, string $plan): void { $this->sendTemplate('gift_received', $email, ['plan' => $plan]); }
    public function sendRefund(User $u): void { $this->sendTemplate('refund_processed', $u->email, ['username' => $u->username, 'first_name' => $u->first_name]); }

    public function testSmtp(SmtpConfiguration $smtp, string $testEmail): array
    {
        try {
            $this->send($smtp, $testEmail, 'Test SMTP — ' . config('app.name'), '<h1>Test OK</h1><p>La configuration SMTP fonctionne.</p>');
            return ['success' => true, 'message' => 'Email envoyé avec succès'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
