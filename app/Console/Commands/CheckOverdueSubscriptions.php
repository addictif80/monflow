<?php

namespace App\Console\Commands;

use App\Models\{Subscription, User, AuditLog};
use App\Services\{NavidromeService, StripeService, EmailService};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckOverdueSubscriptions extends Command
{
    protected $signature = 'subscriptions:check-overdue
        {--keep-data : Suspendre uniquement — ne jamais supprimer les comptes/données, même après le délai de suppression}';
    protected $description = 'Suspend users at J+7 overdue, delete at J+30';

    public function handle(NavidromeService $nd, StripeService $stripe, EmailService $mail): void
    {
        $suspendDays = config('services.monflow.suspend_delay_days', 7);
        $deleteDays = config('services.monflow.delete_delay_days', 30);
        $keepData = (bool) $this->option('keep-data');

        // Get active subscriptions past their period end
        $overdue = Subscription::where('status', 'active')
            ->whereNotNull('current_period_end')
            ->where('current_period_end', '<', now())
            ->with('user')
            ->get();

        foreach ($overdue as $sub) {
            $user = $sub->user;
            if (!$user || $user->status === 'deleted') continue;

            $daysOverdue = (int) now()->diffInDays($sub->current_period_end);

            // J+30: delete (unless --keep-data, in which case suspend only)
            if ($daysOverdue >= $deleteDays) {
                if ($keepData) {
                    if ($user->status === 'active') {
                        $this->suspendUser($user, $sub, $nd, $mail);
                        Log::info("Auto-suspended (data kept) user {$user->username} ({$daysOverdue} days overdue, past delete threshold)");
                    }
                    continue;
                }
                $this->deleteUser($user, $sub, $nd, $stripe, $mail);
                Log::info("Auto-deleted user {$user->username} ({$daysOverdue} days overdue)");
                continue;
            }

            // J+7: suspend
            if ($daysOverdue >= $suspendDays && $user->status === 'active') {
                $this->suspendUser($user, $sub, $nd, $mail);
                Log::info("Auto-suspended user {$user->username} ({$daysOverdue} days overdue)");
            }
        }

        if ($keepData) {
            $this->info('Overdue check completed (données conservées, aucune suppression effectuée).');
            return;
        }

        // Also check already-suspended users for J+30 deletion
        $suspended = Subscription::where('status', 'suspended')
            ->whereNotNull('current_period_end')
            ->where('current_period_end', '<', now()->subDays($deleteDays))
            ->with('user')
            ->get();

        foreach ($suspended as $sub) {
            $user = $sub->user;
            if (!$user || $user->status === 'deleted') continue;
            $this->deleteUser($user, $sub, $nd, $stripe, $mail);
            Log::info("Auto-deleted suspended user {$user->username}");
        }

        $this->info('Overdue check completed.');
    }

    private function suspendUser(User $user, Subscription $sub, NavidromeService $nd, EmailService $mail): void
    {
        $user->update(['status' => 'suspended']);
        $sub->update(['status' => 'suspended']);

        if ($user->navidrome_id) {
            try {
                $nd->suspendUser($user->navidrome_id);
            } catch (\Exception $e) {
                Log::error("Navidrome suspend failed for user {$user->username} ({$user->id}): {$e->getMessage()}");
                AuditLog::record('user.auto_suspend.navidrome_failed', $user, ['error' => $e->getMessage()]);
            }
        } else {
            Log::warning("Auto-suspend: user {$user->username} ({$user->id}) has no navidrome_id — Navidrome account left untouched.");
            AuditLog::record('user.auto_suspend.no_navidrome_id', $user);
        }
        AuditLog::record('user.auto_suspend', $user);
        try { $mail->sendSuspended($user); } catch (\Exception $e) {}
    }

    private function deleteUser(User $user, Subscription $sub, NavidromeService $nd, StripeService $stripe, EmailService $mail): void
    {
        try { $mail->sendDeleted($user); } catch (\Exception $e) {}

        if ($user->navidrome_id) {
            try { $nd->deleteUser($user->navidrome_id); } catch (\Exception $e) {}
        }

        // Cancel Stripe subscriptions
        foreach (Subscription::where('user_id', $user->id)->whereNotNull('stripe_subscription_id')->where('stripe_subscription_id', '!=', '')->get() as $s) {
            try { $stripe->cancelSubscriptionNow($s->stripe_subscription_id); } catch (\Exception $e) {}
        }

        $sub->update(['status' => 'cancelled']);
        $user->update(['status' => 'deleted']);
    }
}
