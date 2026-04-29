<?php

namespace App\Console\Commands;

use App\Models\{Subscription, Notification};
use App\Services\EmailService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendSubscriptionReminders extends Command
{
    protected $signature = 'subscriptions:send-reminders';
    protected $description = 'Send renewal reminder emails 7 days before subscription renewal';

    public function handle(EmailService $mail): void
    {
        $targetDate = now()->addDays(7)->startOfDay();
        $nextDay = $targetDate->copy()->addDay();

        $subs = Subscription::where('status', 'active')
            ->whereNotNull('current_period_end')
            ->whereBetween('current_period_end', [$targetDate, $nextDay])
            ->with('user', 'plan', 'promoCode')
            ->get();

        foreach ($subs as $sub) {
            $user = $sub->user;
            if (!$user || $user->status === 'deleted') continue;

            $renewalPrice = $sub->plan->price;
            $promoEnding = false;

            if ($sub->promo_code_id && $sub->promoCode && $sub->promoCode->is_recurring && $sub->promoCode->recurring_months) {
                $monthsUsed = (int) $sub->current_period_start->diffInMonths(now()) + 1;
                if ($monthsUsed >= $sub->promoCode->recurring_months) {
                    $promoEnding = true;
                }
            }

            try {
                $mail->sendRenewalReminder($user, $sub->plan, $renewalPrice, $promoEnding);
            } catch (\Exception $e) {
                Log::error("Renewal reminder failed for user {$user->id}: {$e->getMessage()}");
            }

            $message = "Votre abonnement {$sub->plan->name} sera renouvelé le {$sub->current_period_end->format('d/m/Y')} au tarif de {$renewalPrice}€.";
            if ($promoEnding) {
                $message .= " Votre code promo arrive à expiration — le tarif normal s'appliquera.";
            }
            $message .= " Vous pouvez résilier depuis votre espace client.";

            Notification::send($user->id, 'renewal_reminder', 'Renouvellement dans 7 jours', $message, '/portal');

            $this->info("Reminder sent to {$user->username}");
        }

        $this->info("Done. {$subs->count()} reminder(s) sent.");
    }
}
