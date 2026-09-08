<?php

namespace App\Console\Commands;

use App\Models\AppSetting;
use App\Services\UrssafReportService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendUrssafReport extends Command
{
    protected $signature = 'urssaf:send-report {--month=} {--force}';
    protected $description = 'Generate and email the monthly URSSAF revenue report for auto-entrepreneur declaration';

    public function handle(UrssafReportService $service): void
    {
        $today = now();
        $settings = AppSetting::current();

        if ($monthOption = $this->option('month')) {
            $month = Carbon::createFromFormat('Y-m', $monthOption)->startOfMonth();
        } else {
            if (!$this->option('force') && (int) $today->day !== (int) $settings->urssaf_report_day) {
                return;
            }
            $month = $today->copy()->subMonthNoOverflow()->startOfMonth();
        }

        $report = $service->generateAndSendForMonth($month);

        if ($report->status === 'sent') {
            $this->info("Rapport URSSAF {$month->format('m/Y')} envoyé à {$report->sent_to}.");
        } else {
            $this->error("Échec de l'envoi du rapport URSSAF {$month->format('m/Y')} : {$report->error}");
        }
    }
}
