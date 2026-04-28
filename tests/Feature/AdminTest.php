<?php

namespace Tests\Feature;

use App\Models\{User, AuditLog, Wallet};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        return User::create([
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);
    }

    private function createUser(): User
    {
        $user = User::create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        Wallet::create(['user_id' => $user->id]);
        return $user;
    }

    public function test_admin_dashboard_loads(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin)->get('/admin')->assertStatus(200);
    }

    public function test_non_admin_cannot_access_admin(): void
    {
        $user = $this->createUser();
        $this->actingAs($user)->get('/admin')->assertStatus(403);
    }

    public function test_suspend_user_creates_audit_log(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();

        $this->actingAs($admin)->post("/admin/users/{$user->id}/suspend");

        $user->refresh();
        $this->assertEquals('suspended', $user->status);
        $this->assertDatabaseHas('audit_logs', [
            'admin_id' => $admin->id,
            'action' => 'user.suspend',
            'target_id' => $user->id,
        ]);
    }

    public function test_reactivate_user(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();
        $user->update(['status' => 'suspended']);

        $this->actingAs($admin)->post("/admin/users/{$user->id}/reactivate");

        $user->refresh();
        $this->assertEquals('active', $user->status);
    }

    public function test_audit_logs_page_loads(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin)->get('/admin/audit-logs')->assertStatus(200);
    }

    public function test_release_email_on_deleted_user(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();
        $user->update(['status' => 'deleted']);
        $originalEmail = $user->email;

        $this->actingAs($admin)->post("/admin/users/{$user->id}/release-email");

        $user->refresh();
        $this->assertStringStartsWith('released_', $user->email);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.release_email']);
    }

    public function test_release_email_fails_for_active_user(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();

        $response = $this->actingAs($admin)->post("/admin/users/{$user->id}/release-email");
        $response->assertSessionHas('error');
    }
}
