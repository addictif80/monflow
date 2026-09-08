<?php

namespace Tests\Feature;

use App\Models\{AppSetting, Payment, User, UrssafReport};
use App\Services\UrssafReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Hash, Storage};
use Tests\TestCase;

class UrssafReportTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(): User
    {
        return User::create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
    }

    public function test_generate_for_month_creates_pdf_with_correct_total(): void
    {
        Storage::fake('local');
        $user = $this->createUser();
        $month = now()->startOfMonth();

        Payment::create(['user_id' => $user->id, 'amount' => 10, 'status' => 'succeeded', 'payment_method' => 'stripe', 'description' => 'Plan A', 'created_at' => $month->copy()->addDays(2)]);
        Payment::create(['user_id' => $user->id, 'amount' => 25.50, 'status' => 'succeeded', 'payment_method' => 'wallet', 'description' => 'Plan B', 'created_at' => $month->copy()->addDays(5)]);
        Payment::create(['user_id' => $user->id, 'amount' => 99, 'status' => 'failed', 'payment_method' => 'stripe', 'description' => 'Refused', 'created_at' => $month->copy()->addDays(6)]);

        $service = app(UrssafReportService::class);
        $report = $service->generateForMonth($month);

        $this->assertEquals(2, $report->transaction_count);
        $this->assertEquals(35.50, (float) $report->total_amount);
        Storage::disk('local')->assertExists($report->pdf_path);
    }

    public function test_send_report_fails_gracefully_without_recipient_email(): void
    {
        Storage::fake('local');
        $user = $this->createUser();
        $month = now()->startOfMonth();
        Payment::create(['user_id' => $user->id, 'amount' => 10, 'status' => 'succeeded', 'payment_method' => 'stripe', 'description' => 'Plan A', 'created_at' => $month]);

        $service = app(UrssafReportService::class);
        $report = $service->generateAndSendForMonth($month);

        $this->assertEquals('failed', $report->status);
        $this->assertNotNull($report->error);
    }

    public function test_admin_can_update_urssaf_settings(): void
    {
        $admin = User::create(['username' => 'admin', 'email' => 'admin@example.com', 'password' => Hash::make('password'), 'is_admin' => true, 'email_verified_at' => now()]);

        $this->actingAs($admin)->post('/admin/settings/urssaf-report', [
            'urssaf_report_day' => 5,
            'urssaf_report_email' => 'compta@example.com',
        ])->assertRedirect();

        $settings = AppSetting::current();
        $this->assertEquals(5, $settings->urssaf_report_day);
        $this->assertEquals('compta@example.com', $settings->urssaf_report_email);
    }

    public function test_command_skips_when_day_does_not_match(): void
    {
        $settings = AppSetting::current();
        $settings->update(['urssaf_report_day' => now()->day === 1 ? 2 : 1]);

        $this->artisan('urssaf:send-report')->assertExitCode(0);
        $this->assertEquals(0, UrssafReport::count());
    }
}
