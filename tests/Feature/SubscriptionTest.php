<?php

namespace Tests\Feature;

use App\Models\{User, Plan, Subscription, Wallet};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(): User
    {
        $user = User::create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        Wallet::create(['user_id' => $user->id, 'balance' => 100]);
        return $user;
    }

    private function createPlan(): Plan
    {
        return Plan::create([
            'name' => 'Premium',
            'price' => 9.99,
            'billing_cycle' => 'monthly',
            'stripe_price_id' => 'price_test123',
            'max_devices' => 3,
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    public function test_plans_page_loads(): void
    {
        $user = $this->createUser();
        $this->actingAs($user)->get('/portal/plans')->assertStatus(200);
    }

    public function test_wallet_pay_creates_subscription(): void
    {
        $user = $this->createUser();
        $plan = $this->createPlan();

        $response = $this->actingAs($user)->post('/portal/wallet-pay', [
            'plan_id' => $plan->id,
            'months' => 1,
        ]);

        $response->assertRedirect('/portal');
        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);
        $user->refresh();
        $this->assertEquals(100 - 9.99, $user->wallet->balance);
    }

    public function test_wallet_pay_fails_with_insufficient_balance(): void
    {
        $user = $this->createUser();
        $user->wallet->update(['balance' => 5]);
        $plan = $this->createPlan();

        $response = $this->actingAs($user)->post('/portal/wallet-pay', [
            'plan_id' => $plan->id,
            'months' => 1,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('subscriptions', ['user_id' => $user->id]);
    }

    public function test_wallet_pay_multi_month(): void
    {
        $user = $this->createUser();
        $plan = $this->createPlan();

        $response = $this->actingAs($user)->post('/portal/wallet-pay', [
            'plan_id' => $plan->id,
            'months' => 3,
        ]);

        $response->assertRedirect('/portal');
        $sub = Subscription::where('user_id', $user->id)->first();
        $this->assertNotNull($sub);
        $this->assertTrue($sub->current_period_end->gt(now()->addDays(85)));
    }

    public function test_cannot_subscribe_with_active_subscription(): void
    {
        $user = $this->createUser();
        $plan = $this->createPlan();
        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_start' => now(),
            'current_period_end' => now()->addDays(30),
        ]);

        $response = $this->actingAs($user)->post('/portal/wallet-pay', [
            'plan_id' => $plan->id,
            'months' => 1,
        ]);

        $response->assertSessionHas('error');
    }
}
