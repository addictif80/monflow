<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\NavidromeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Force l'invariant : un utilisateur non-admin doit avoir un abonnement
 * actif pour que son compte Navidrome soit accessible.
 *
 * - Sans abonnement actif → le mot de passe Navidrome est randomisé (suspend)
 * - Avec abonnement actif → le mot de passe Navidrome est restauré depuis
 *   encrypted_password (reactivate)
 *
 * À lancer une fois pour rattraper les comptes créés avant le fix de
 * sécurité, puis en cron périodique comme filet de sécurité.
 */
class SyncNavidromeAccess extends Command
{
    protected $signature = 'navidrome:sync {--dry-run : Affiche ce qui serait fait sans rien modifier}';
    protected $description = 'Synchronise l\'accès Navidrome avec l\'état des abonnements (suspend/reactivate)';

    public function handle(NavidromeService $nd): int
    {
        $dry = $this->option('dry-run');
        $suspended = $reactivated = $skipped = 0;

        $users = User::whereNotNull('navidrome_id')
            ->where('is_admin', false)
            ->where('status', '!=', 'deleted')
            ->with('activeSubscription')
            ->get();

        foreach ($users as $user) {
            $hasActive = (bool) $user->activeSubscription;

            if (!$hasActive) {
                $this->line("[SUSPEND] {$user->username} — aucun abonnement actif");
                if (!$dry) {
                    try {
                        $nd->suspendUser($user->navidrome_id);
                        $suspended++;
                    } catch (\Exception $e) {
                        Log::error("navidrome:sync suspend failed for {$user->username}: {$e->getMessage()}");
                        $this->error("  ↳ erreur: {$e->getMessage()}");
                    }
                }
            } else {
                $plain = $user->getDecryptedPassword();
                if (!$plain) {
                    $this->warn("[SKIP]    {$user->username} — abonnement actif mais aucun mot de passe chiffré stocké");
                    $skipped++;
                    continue;
                }
                $this->line("[REACTIVATE] {$user->username} — abonnement actif");
                if (!$dry) {
                    try {
                        $nd->reactivateUser($user->navidrome_id, $plain);
                        $reactivated++;
                    } catch (\Exception $e) {
                        Log::error("navidrome:sync reactivate failed for {$user->username}: {$e->getMessage()}");
                        $this->error("  ↳ erreur: {$e->getMessage()}");
                    }
                }
            }
        }

        $this->info($dry ? 'Dry-run terminé.' : "Synchronisation terminée : {$suspended} suspendus, {$reactivated} réactivés, {$skipped} ignorés.");
        return self::SUCCESS;
    }
}
