<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Services\EmailService;
use Illuminate\Console\Command;

class SendPaymentReminders extends Command
{
    protected $signature = 'subscriptions:send-payment-reminders';
    protected $description = 'Send payment reminders to users with expiring or overdue subscriptions (daily relance)';

    public function handle(EmailService $mail): void
    {
        // Remind 3 days before expiry
        Subscription::where('status', 'active')
            ->whereNotNull('current_period_end')
            ->whereBetween('current_period_end', [now(), now()->addDays(3)])
            ->with('user')
            ->each(function ($sub) use ($mail) {
                $days = (int) now()->diffInDays($sub->current_period_end, true);
                try { $mail->sendPaymentReminder($sub->user, $days); } catch (\Exception $e) {}
            });

        // Remind overdue users (daily until suspend)
        Subscription::where('status', 'active')
            ->whereNotNull('current_period_end')
            ->where('current_period_end', '<', now())
            ->with('user')
            ->each(function ($sub) use ($mail) {
                $days = (int) now()->diffInDays($sub->current_period_end, true);
                try { $mail->sendPaymentReminder($sub->user, $days); } catch (\Exception $e) {}
            });

        $this->info('Reminders sent.');
    }
}
