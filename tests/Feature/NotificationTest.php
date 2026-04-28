<?php

namespace Tests\Feature;

use App\Models\{User, Notification};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class NotificationTest extends TestCase
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

    public function test_notifications_page_loads(): void
    {
        $user = $this->createUser();
        $this->actingAs($user)->get('/portal/notifications')->assertStatus(200);
    }

    public function test_mark_notifications_as_read(): void
    {
        $user = $this->createUser();
        Notification::push($user->id, 'test', 'Test', 'Test body');
        Notification::push($user->id, 'test', 'Test 2', 'Test body 2');

        $this->assertEquals(2, Notification::where('user_id', $user->id)->unread()->count());

        $this->actingAs($user)->post('/portal/notifications/read');

        $this->assertEquals(0, Notification::where('user_id', $user->id)->unread()->count());
    }

    public function test_notification_push_static_method(): void
    {
        $user = $this->createUser();
        Notification::push($user->id, 'payment_success', 'Paiement OK', 'Votre paiement a été traité.', '/portal');

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => 'payment_success',
        ]);
    }
}
