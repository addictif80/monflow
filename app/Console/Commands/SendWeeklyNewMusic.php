<?php

namespace App\Console\Commands;

use App\Models\{User, Newsletter};
use App\Services\{NavidromeService, EmailService};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendWeeklyNewMusic extends Command
{
    protected $signature = 'newsletter:weekly-new-music';
    protected $description = 'Send weekly email with new albums and top artists';

    public function handle(NavidromeService $nd, EmailService $mail): void
    {
        try {
            $albums = $nd->getRecentAlbums(10, now()->subDays(7));
            $topArtists = $nd->getTopPlayedArtists(5);
        } catch (\Exception $e) {
            $this->error("Failed to fetch from Navidrome: {$e->getMessage()}");
            return;
        }

        if (empty($albums) && empty($topArtists)) {
            $this->info('No new music and no play data, skipping.');
            return;
        }

        $html = $this->buildEmail($albums, $topArtists);
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
        $this->info("Weekly newsletter sent to {$sent} subscriber(s) with " . count($albums) . " album(s) and " . count($topArtists) . " top artist(s).");
    }

    public static function buildEmail(array $albums, array $topArtists): string
    {
        $albumCards = '';
        foreach (array_slice($albums, 0, 10) as $album) {
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

        $artistCards = '';
        foreach ($topArtists as $i => $artist) {
            $name = htmlspecialchars($artist['name'] ?? 'Inconnu');
            $playCount = $artist['playCount'] ?? 0;
            $albumCount = $artist['albumCount'] ?? 0;
            $rank = $i + 1;
            $medal = match($rank) { 1 => '&#129351;', 2 => '&#129352;', 3 => '&#129353;', default => "<span style=\"display:inline-block;width:24px;text-align:center;font-weight:700;color:#71717a\">{$rank}</span>" };

            $artistCards .= <<<HTML
            <tr><td style="padding:8px 0;border-bottom:1px solid #f4f4f5">
              <table cellpadding="0" cellspacing="0" width="100%"><tr>
                <td width="32" style="vertical-align:middle;text-align:center;font-size:18px">{$medal}</td>
                <td style="vertical-align:top;padding-left:8px">
                  <div style="font-weight:600;font-size:14px;color:#18181b">{$name}</div>
                  <div style="font-size:13px;color:#71717a;margin-top:2px">{$playCount} écoute(s) &middot; {$albumCount} album(s)</div>
                </td>
              </tr></table>
            </td></tr>
            HTML;
        }

        $albumsSection = '';
        if (!empty($albums)) {
            $albumsSection = <<<HTML
            <h2 style="margin:0 0 16px;font-size:18px;font-weight:600;color:#18181b">Ajouts récents</h2>
            <table width="100%" cellpadding="0" cellspacing="0">{$albumCards}</table>
            HTML;
        }

        $artistsSection = '';
        if (!empty($topArtists)) {
            $artistsSection = <<<HTML
            <h2 style="margin:28px 0 16px;font-size:18px;font-weight:600;color:#18181b">Top 5 artistes les plus écoutés</h2>
            <table width="100%" cellpadding="0" cellspacing="0">{$artistCards}</table>
            HTML;
        }

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
    <p style="margin:0 0 24px;font-size:15px;color:#71717a">Voici les nouveautés de la semaine sur MonFlow.</p>
    {$albumsSection}
    {$artistsSection}
    <table cellpadding="0" cellspacing="0" style="margin:28px auto 0"><tr><td style="background:#6366f1;border-radius:8px;padding:14px 28px"><a href="{{ site_url }}/player" style="color:#ffffff;text-decoration:none;font-weight:600;font-size:14px">Écouter maintenant</a></td></tr></table>
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
