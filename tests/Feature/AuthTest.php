<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Hash, URL};
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_loads(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    public function test_register_page_loads(): void
    {
        $response = $this->get('/register');
        $response->assertStatus(200);
    }

    public function test_login_with_valid_credentials(): void
    {
        $user = User::create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        $response = $this->post('/login', [
            'username' => 'testuser',
            'password' => 'password123',
        ]);
        $response->assertRedirect('/portal');
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        $response = $this->post('/login', [
            'username' => 'testuser',
            'password' => 'wrongpassword',
        ]);
        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_login_blocked_for_unverified_email(): void
    {
        User::create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/login', [
            'username' => 'testuser',
            'password' => 'password123',
        ]);
        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_login_blocked_for_suspended_user(): void
    {
        User::create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'status' => 'suspended',
            'email_verified_at' => now(),
        ]);

        $response = $this->post('/login', [
            'username' => 'testuser',
            'password' => 'password123',
        ]);
        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_admin_login_redirects_to_admin(): void
    {
        $admin = User::create([
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        $response = $this->post('/login', [
            'username' => 'admin',
            'password' => 'password123',
        ]);
        $response->assertRedirect('/admin');
    }

    public function test_authenticated_user_redirected_from_login(): void
    {
        $user = User::create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)->get('/login')->assertRedirect('/portal');
    }

    public function test_rate_limiting_on_login(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $response = $this->post('/login', [
                'username' => 'nonexistent',
                'password' => 'wrong',
            ]);
        }
        $response->assertStatus(429);
    }

    public function test_register_blocked_with_deleted_account_email_and_kept_username(): void
    {
        $deleted = User::create([
            'username' => 'olduser',
            'email' => 'old@example.com',
            'password' => Hash::make('password123'),
            'status' => 'deleted',
        ]);

        $response = $this->post('/register', [
            'username' => 'brandnew',
            'email' => 'old@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('supprimé', session('errors')->first('email'));
        $this->assertDatabaseMissing('users', ['username' => 'brandnew']);
        $deleted->refresh();
        $this->assertEquals('old@example.com', $deleted->email);
    }

    public function test_resubscribe_link_releases_email_for_reuse(): void
    {
        $deleted = User::create([
            'username' => 'olduser',
            'email' => 'old@example.com',
            'password' => Hash::make('password123'),
            'status' => 'deleted',
        ]);

        $url = URL::temporarySignedRoute('resubscribe', now()->addDays(30), ['id' => $deleted->id]);
        $response = $this->get($url);

        $response->assertRedirect('/register');
        $response->assertSessionHas('success');
        $deleted->refresh();
        $this->assertStringStartsWith('released_', $deleted->email);
        $this->assertStringEndsWith('old@example.com', $deleted->email);

        // L'adresse d'origine est maintenant libre pour une nouvelle inscription.
        $response = $this->post('/register', [
            'username' => 'brandnew',
            'email' => 'old@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $response->assertSessionDoesntHaveErrors('email');
        $this->assertDatabaseHas('users', ['username' => 'brandnew', 'email' => 'old@example.com']);
    }

    public function test_resubscribe_link_rejects_invalid_signature(): void
    {
        $deleted = User::create([
            'username' => 'olduser',
            'email' => 'old@example.com',
            'password' => Hash::make('password123'),
            'status' => 'deleted',
        ]);

        $response = $this->get("/resubscribe/{$deleted->id}");
        $response->assertStatus(403);
        $deleted->refresh();
        $this->assertEquals('old@example.com', $deleted->email);
    }

    public function test_resend_resubscribe_email_for_deleted_account(): void
    {
        User::create([
            'username' => 'olduser',
            'email' => 'old@example.com',
            'password' => Hash::make('password123'),
            'status' => 'deleted',
        ]);

        $response = $this->post('/resubscribe/resend', ['email' => 'old@example.com']);

        $response->assertRedirect('/register');
        $response->assertSessionHas('success');
    }

    public function test_resend_resubscribe_email_gives_generic_response_for_unknown_email(): void
    {
        $response = $this->post('/resubscribe/resend', ['email' => 'unknown@example.com']);

        $response->assertRedirect('/register');
        $response->assertSessionHas('success');
    }

    public function test_resend_resubscribe_email_does_nothing_for_active_account(): void
    {
        User::create([
            'username' => 'activeuser',
            'email' => 'active@example.com',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $response = $this->post('/resubscribe/resend', ['email' => 'active@example.com']);

        $response->assertRedirect('/register');
        $response->assertSessionHas('success');
    }
}
