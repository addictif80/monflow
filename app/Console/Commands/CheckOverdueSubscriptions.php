<?php

namespace App\Console\Commands;

use App\Models\{Subscription, User, AuditLog};
use App\Services\{NavidromeService, StripeService, EmailService};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckOverdueSubscriptions extends Command
{
    protected $signature = 'subscriptions:check-overdue
        {--keep-data : Suspendre uniquement — ne jamais supprimer les comptes/données, même après le délai de suppression}
        {--dry-run : Prévisualiser les comptes concernés sans rien modifier ni envoyer de mail}';
    protected $description = 'Suspend users at J+7 overdue, delete at J+30';

    public function handle(NavidromeService $nd, StripeService $stripe, EmailService $mail): void
    {
        $suspendDays = config('services.monflow.suspend_delay_days', 7);
        $deleteDays = config('services.monflow.delete_delay_days', 30);
        $keepData = (bool) $this->option('keep-data');
        $dryRun = (bool) $this->option('dry-run');

        $this->line("Config : suspend_delay_days={$suspendDays}, delete_delay_days={$deleteDays}, keep-data=" . ($keepData ? 'oui' : 'non') . ($dryRun ? ', APERÇU (aucune modification)' : ''));

        // Get active subscriptions past their period end
        $overdue = Subscription::where('status', 'active')
            ->whereNotNull('current_period_end')
            ->where('current_period_end', '<', now())
            ->with('user')
            ->get();

        $this->line("Abonnements 'active' en retard trouvés : {$overdue->count()}");

        foreach ($overdue as $sub) {
            $user = $sub->user;
            if (!$user) {
                $this->line("- abonnement {$sub->id} : ignoré (aucun utilisateur associé)");
                continue;
            }
            if ($user->status === 'deleted') {
                $this->line("- {$user->username} : ignoré (utilisateur déjà supprimé, abonnement laissé en l'état — désynchronisation)");
                continue;
            }

            // Carbon 3 returns a signed diff by default (negative for past dates) —
            // force the absolute value so this behaves the same as it did on Carbon 2.
            $daysOverdue = (int) now()->diffInDays($sub->current_period_end, true);

            // J+30: delete (unless --keep-data, in which case suspend only)
            if ($daysOverdue >= $deleteDays) {
                if ($keepData) {
                    if ($user->status === 'active') {
                        if ($dryRun) {
                            $this->line("- {$user->username} : SERA SUSPENDU ({$daysOverdue}j de retard, données conservées)");
                        } else {
                            $this->suspendUser($user, $sub, $nd, $mail);
                            $this->line("- {$user->username} : suspendu ({$daysOverdue}j de retard, données conservées)");
                            Log::info("Auto-suspended (data kept) user {$user->username} ({$daysOverdue} days overdue, past delete threshold)");
                        }
                    } else {
                        $this->line("- {$user->username} : ignoré (statut déjà '{$user->status}', pas 'active')");
                    }
                    continue;
                }
                if ($dryRun) {
                    $this->line("- {$user->username} : SERA SUPPRIMÉ ({$daysOverdue}j de retard) — compte Navidrome et données effacés");
                } else {
                    $this->deleteUser($user, $sub, $nd, $stripe, $mail);
                    $this->line("- {$user->username} : supprimé ({$daysOverdue}j de retard)");
                    Log::info("Auto-deleted user {$user->username} ({$daysOverdue} days overdue)");
                }
                continue;
            }

            // J-7 before deletion: warn the user their data will soon be erased
            if (!$keepData && !$dryRun) {
                $this->maybeSendDeletionWarning($sub, $user, $daysOverdue, $deleteDays, $mail);
            }

            // J+7: suspend
            if ($daysOverdue >= $suspendDays && $user->status === 'active') {
                if ($dryRun) {
                    $this->line("- {$user->username} : SERA SUSPENDU ({$daysOverdue}j de retard)");
                } else {
                    $this->suspendUser($user, $sub, $nd, $mail);
                    $this->line("- {$user->username} : suspendu ({$daysOverdue}j de retard)");
                    Log::info("Auto-suspended user {$user->username} ({$daysOverdue} days overdue)");
                }
            } else {
                $this->line("- {$user->username} : aucune action ({$daysOverdue}j de retard, statut '{$user->status}')");
            }
        }

        if ($keepData) {
            $this->info($dryRun
                ? 'Aperçu terminé (données conservées, aucune suppression ne sera jamais effectuée).'
                : 'Overdue check completed (données conservées, aucune suppression effectuée).');
            return;
        }

        if ($dryRun) {
            // Skip the deletion-warning email pass and the suspended->delete pass's
            // side effects during a preview — only report what would be deleted.
            $suspended = Subscription::where('status', 'suspended')
                ->whereNotNull('current_period_end')
                ->where('current_period_end', '<', now()->subDays($deleteDays))
                ->with('user')
                ->get();

            $this->line("Abonnements 'suspended' au-delà du délai de suppression : {$suspended->count()}");

            foreach ($suspended as $sub) {
                $user = $sub->user;
                if (!$user || $user->status === 'deleted') continue;
                $this->line("- {$user->username} : SERA SUPPRIMÉ (suspendu depuis plus de {$deleteDays}j)");
            }

            $this->info('Aperçu terminé (aucune modification effectuée).');
            return;
        }

        // Suspended users nearing J+30: warn 7 days before deletion
        $approaching = Subscription::where('status', 'suspended')
            ->whereNotNull('current_period_end')
            ->whereNull('deletion_warning_sent_at')
            ->where('current_period_end', '<', now()->subDays($deleteDays - 7))
            ->where('current_period_end', '>=', now()->subDays($deleteDays))
            ->with('user')
            ->get();

        foreach ($approaching as $sub) {
            $user = $sub->user;
            if (!$user || $user->status === 'deleted') continue;
            // Carbon 3 returns a signed diff by default (negative for past dates) —
            // force the absolute value so this behaves the same as it did on Carbon 2.
            $daysOverdue = (int) now()->diffInDays($sub->current_period_end, true);
            $this->maybeSendDeletionWarning($sub, $user, $daysOverdue, $deleteDays, $mail);
        }

        // Also check already-suspended users for J+30 deletion
        $suspended = Subscription::where('status', 'suspended')
            ->whereNotNull('current_period_end')
            ->where('current_period_end', '<', now()->subDays($deleteDays))
            ->with('user')
            ->get();

        $this->line("Abonnements 'suspended' au-delà du délai de suppression : {$suspended->count()}");

        foreach ($suspended as $sub) {
            $user = $sub->user;
            if (!$user || $user->status === 'deleted') continue;
            $this->deleteUser($user, $sub, $nd, $stripe, $mail);
            $this->line("- {$user->username} : supprimé (suspendu depuis plus de {$deleteDays}j)");
            Log::info("Auto-deleted suspended user {$user->username}");
        }

        $this->info('Overdue check completed.');
    }

    private function maybeSendDeletionWarning(Subscription $sub, User $user, int $daysOverdue, int $deleteDays, EmailService $mail): void
    {
        if ($sub->deletion_warning_sent_at) return;

        $daysLeft = $deleteDays - $daysOverdue;
        if ($daysLeft < 0 || $daysLeft > 7) return;

        $deletionDate = now()->addDays($daysLeft);
        try {
            $mail->sendDeletionWarning($user, $daysLeft, $deletionDate);
        } catch (\Exception $e) {
            Log::error("Deletion warning email failed for user {$user->username} ({$user->id}): {$e->getMessage()}");
        }
        $sub->update(['deletion_warning_sent_at' => now()]);
        AuditLog::record('user.deletion_warning_sent', $user, ['days_left' => $daysLeft]);
        Log::info("Sent deletion warning to {$user->username} ({$daysLeft} days left)");
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
