<?php

namespace App\Console\Commands;

use App\Models\{User, Wallet, Subscription, Plan};
use App\Services\NavidromeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ImportNavidromeUsers extends Command
{
    protected $signature = 'navidrome:import
        {--dry-run : Afficher les actions sans les exécuter}
        {--with-subscription= : Attribuer un abonnement actif (ID du plan)}
        {--months=1 : Durée de l\'abonnement en mois}';

    protected $description = 'Import existing Navidrome users into MonFlow panel';

    public function handle(NavidromeService $nd): void
    {
        $dryRun = $this->option('dry-run');
        $planId = $this->option('with-subscription');
        $months = max(1, (int) $this->option('months'));

        $plan = null;
        if ($planId) {
            $plan = Plan::find($planId);
            if (!$plan) {
                $this->error("Plan {$planId} introuvable.");
                $this->line('Plans disponibles :');
                Plan::all()->each(fn ($p) => $this->line("  {$p->id} — {$p->name} ({$p->price}€/{$p->billing_cycle})"));
                return;
            }
        }

        $this->info('Récupération des utilisateurs Navidrome...');

        try {
            $ndUsers = $nd->listUsers();
        } catch (\Exception $e) {
            $this->error("Impossible de contacter Navidrome : {$e->getMessage()}");
            return;
        }

        if (empty($ndUsers)) {
            $this->warn('Aucun utilisateur trouvé dans Navidrome.');
            return;
        }

        $this->info(count($ndUsers) . ' utilisateur(s) trouvé(s) dans Navidrome.');
        $this->newLine();

        $imported = 0;
        $linked = 0;
        $skipped = 0;

        foreach ($ndUsers as $ndUser) {
            $ndId = $ndUser['id'] ?? null;
            $username = $ndUser['userName'] ?? '';
            $email = $ndUser['email'] ?? '';
            $name = $ndUser['name'] ?? '';
            $isAdmin = $ndUser['isAdmin'] ?? false;

            if (!$ndId || !$username) {
                $this->warn("  Utilisateur ignoré (pas d'ID ou username).");
                $skipped++;
                continue;
            }

            // Skip Navidrome admin account
            if ($isAdmin) {
                $this->line("  <comment>SKIP</comment> {$username} (admin Navidrome)");
                $skipped++;
                continue;
            }

            // Already linked in MonFlow?
            $existing = User::where('navidrome_id', $ndId)->first();
            if ($existing) {
                $this->line("  <info>OK</info>   {$username} — déjà lié (MonFlow: {$existing->username})");
                $skipped++;
                continue;
            }

            // Match by username or email?
            $match = User::where('username', $username)->first();
            if (!$match && $email) {
                $match = User::where('email', $email)->first();
            }

            if ($match) {
                if ($dryRun) {
                    $this->line("  <comment>LINK</comment> {$username} → MonFlow #{$match->username} (dry-run)");
                } else {
                    $match->update(['navidrome_id' => $ndId]);
                    $this->line("  <info>LINK</info> {$username} → MonFlow #{$match->username}");
                }
                $linked++;
                continue;
            }

            // New import
            $nameParts = explode(' ', $name, 2);
            $firstName = $nameParts[0] ?? '';
            $lastName = $nameParts[1] ?? '';

            if ($dryRun) {
                $this->line("  <comment>NEW</comment>  {$username} ({$email}) (dry-run)");
            } else {
                $tempPassword = bin2hex(random_bytes(16));
                $user = User::create([
                    'username' => $username,
                    'email' => $email ?: "{$username}@imported.local",
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'password' => Hash::make($tempPassword),
                    'navidrome_id' => $ndId,
                    'email_verified_at' => now(),
                    'status' => 'active',
                ]);
                Wallet::create(['user_id' => $user->id]);

                if ($plan) {
                    $days = $plan->period_days * $months;
                    Subscription::create([
                        'user_id' => $user->id,
                        'plan_id' => $plan->id,
                        'status' => 'active',
                        'current_period_start' => now(),
                        'current_period_end' => now()->addDays($days),
                    ]);
                }

                $this->line("  <info>NEW</info>  {$username} ({$email}) — importé" . ($plan ? " + abo {$plan->name} ({$months} mois)" : ''));
            }
            $imported++;
        }

        $this->newLine();
        $this->table(['Action', 'Nombre'], [
            ['Importés (nouveaux)', $imported],
            ['Liés (existants)', $linked],
            ['Ignorés (admin/déjà liés)', $skipped],
        ]);

        if ($dryRun) {
            $this->warn('Mode dry-run — aucune modification effectuée. Relancez sans --dry-run pour appliquer.');
        } else {
            if ($imported > 0) {
                $this->newLine();
                $this->warn('Les utilisateurs importés n\'ont pas de mot de passe connu côté panel.');
                $this->warn('Ils devront utiliser "Mot de passe oublié" pour se connecter au portail.');
                $this->warn('Cela synchronisera automatiquement leur mot de passe Navidrome.');
            }
        }
    }
}
