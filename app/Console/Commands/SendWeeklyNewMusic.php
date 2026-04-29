<?php

namespace App\Console\Commands;

use App\Models\{User, Newsletter};
use App\Services\{NavidromeService, EmailService};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendWeeklyNewMusic extends Command
{
    protected $signature = 'newsletter:weekly-new-music';
    protected $description = 'Send weekly email with new tracks added to the server';

    public function handle(NavidromeService $nd, EmailService $mail): void
    {
        try {
            $albums = $nd->getRecentAlbums(20, 7);
        } catch (\Exception $e) {
            $this->error("Failed to fetch from Navidrome: {$e->getMessage()}");
            return;
        }

        if (empty($albums)) {
            $this->info('No new music this week, skipping.');
            return;
        }

        $html = $this->buildEmail($albums);
        $subject = 'Les nouveautés de la semaine sur {{ site_name }}';

        $nl = Newsletter::create(['subject' => $subject, 'html_body' => $html, 'status' => 'sending']);

        $recipients = User::where('is_admin', false)
            ->where('status', '!=', 'deleted')
            ->where('newsletter_optin', true)
            ->whereNotNull('email_verified_at')
            ->get();

        $sent = 0;
        foreach ($recipients as $user) {
            try {
                $mail->sendNewsletterNow($user, $subject, $html);
                $sent++;
            } catch (\Exception $e) {
                Log::error("Weekly newsletter failed for {$user->email}: {$e->getMessage()}");
            }
        }

        $nl->update(['status' => 'sent', 'recipients_count' => $sent, 'sent_at' => now()]);
        $this->info("Weekly newsletter sent to {$sent} subscriber(s) with " . count($albums) . " new album(s).");
    }

    private function buildEmail(array $albums): string
    {
        $albumCards = '';
        foreach (array_slice($albums, 0, 12) as $album) {
            $title = htmlspecialchars($album['name'] ?? 'Sans titre');
            $artist = htmlspecialchars($album['albumArtist'] ?? $album['artist'] ?? 'Artiste inconnu');
            $songCount = $album['songCount'] ?? 0;
            $year = $album['year'] ?? '';
            $yearBadge = $year ? "<span style=\"display:inline-block;background:#f4f4f5;color:#71717a;font-size:11px;padding:2px 6px;border-radius:4px;margin-left:4px\">{$year}</span>" : '';

            $albumCards .= <<<HTML
            <tr><td style="padding:8px 0;border-bottom:1px solid #f4f4f5">
              <table cellpadding="0" cellspacing="0" width="100%"><tr>
                <td width="48" style="vertical-align:top;padding-right:12px">
                  <div style="width:48px;height:48px;background:#e4e4e7;border-radius:6px;text-align:center;line-height:48px;font-size:20px;color:#a1a1aa">&#9835;</div>
                </td>
                <td style="vertical-align:top">
                  <div style="font-weight:600;font-size:14px;color:#18181b">{$title}{$yearBadge}</div>
                  <div style="font-size:13px;color:#71717a;margin-top:2px">{$artist} &middot; {$songCount} titre(s)</div>
                </td>
              </tr></table>
            </td></tr>
            HTML;
        }

        $count = count($albums);
        $moreText = $count > 12 ? "<p style=\"text-align:center;font-size:13px;color:#71717a;margin:16px 0 0\">Et " . ($count - 12) . " autre(s) album(s)...</p>" : '';

        return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f5;padding:40px 20px">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%">
  <tr><td style="background:#18181b;border-radius:12px 12px 0 0;padding:32px 40px;text-align:center">
    <img src="https://monflow.fr/assets/img/spotiflix%20(1).png" alt="MonFlow" width="160" style="display:block;margin:0 auto">
  </td></tr>
  <tr><td style="background:#ffffff;padding:40px;border-left:1px solid #e4e4e7;border-right:1px solid #e4e4e7">
    <h1 style="margin:0 0 8px;font-size:22px;font-weight:700;color:#18181b">Les nouveautés de la semaine</h1>
    <p style="margin:0 0 24px;font-size:15px;color:#71717a">Voici les derniers albums ajoutés sur votre serveur MonFlow.</p>
    <table width="100%" cellpadding="0" cellspacing="0">
      {$albumCards}
    </table>
    {$moreText}
    <table cellpadding="0" cellspacing="0" style="margin:28px auto 0"><tr><td style="background:#6366f1;border-radius:8px;padding:14px 28px"><a href="{{ site_url }}/player" style="color:#ffffff;text-decoration:none;font-weight:600;font-size:14px">Ecouter maintenant</a></td></tr></table>
  </td></tr>
  <tr><td style="background:#fafafa;border-radius:0 0 12px 12px;padding:24px 40px;border:1px solid #e4e4e7;border-top:none;text-align:center">
    <p style="margin:0;font-size:12px;color:#a1a1aa;line-height:1.5">{{ site_name }} &mdash; Votre musique, votre espace</p>
  </td></tr>
</table>
</td></tr>
</table>
</body>
</html>
HTML;
    }
}
