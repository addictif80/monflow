<?php

namespace App\Services;

use App\Models\{AppSetting, Payment, UrssafReport};
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\{Log, Storage};

class UrssafReportService
{
    public function __construct(private EmailService $mail) {}

    public function generateForMonth(Carbon $monthStart): UrssafReport
    {
        $start = $monthStart->copy()->startOfMonth();
        $end = $monthStart->copy()->endOfMonth();
        $periodLabel = self::frenchMonths()[$start->month] . ' ' . $start->format('Y');

        $payments = Payment::where('status', 'succeeded')
            ->whereBetween('created_at', [$start, $end])
            ->with('user')
            ->orderBy('created_at')
            ->get();

        $total = $payments->sum('amount');

        $html = view('admin.urssaf.report-pdf', [
            'periodLabel' => $periodLabel,
            'start' => $start,
            'end' => $end,
            'payments' => $payments,
            'total' => $total,
        ])->render();

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $relativePath = 'urssaf-reports/' . $start->format('Y-m') . '.pdf';
        Storage::disk('local')->put($relativePath, $dompdf->output());

        return UrssafReport::updateOrCreate(
            ['period_month' => $start->format('Y-m-d')],
            [
                'total_amount' => $total,
                'transaction_count' => $payments->count(),
                'pdf_path' => $relativePath,
                'status' => 'pending',
                'sent_to' => null,
                'error' => null,
            ]
        );
    }

    public function sendReport(UrssafReport $report): void
    {
        $toEmail = AppSetting::current()->urssaf_report_email;

        if (!$toEmail) {
            $report->update(['status' => 'failed', 'error' => 'Aucune adresse email de destination configurée.']);
            return;
        }

        $periodLabel = $report->period_month->format('m/Y');
        $absolutePath = Storage::disk('local')->path($report->pdf_path);

        try {
            $this->mail->sendUrssafReport($toEmail, $periodLabel, (float) $report->total_amount, $report->transaction_count, $absolutePath);
            $report->update(['status' => 'sent', 'sent_to' => $toEmail, 'sent_at' => now(), 'error' => null]);
        } catch (\Throwable $e) {
            Log::error("Failed to send URSSAF report for {$periodLabel}: {$e->getMessage()}");
            $report->update(['status' => 'failed', 'error' => $e->getMessage()]);
        }
    }

    public function generateAndSendForMonth(Carbon $monthStart): UrssafReport
    {
        $report = $this->generateForMonth($monthStart);
        $this->sendReport($report);
        return $report;
    }

    private static function frenchMonths(): array
    {
        return [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
        ];
    }
}
