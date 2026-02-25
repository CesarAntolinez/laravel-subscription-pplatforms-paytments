<?php

namespace Tests\Feature;

use App\Models\Discount;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * HU-E2 — Apply discount code during checkout
 */
class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function makeUserWithToken(): array
    {
        $user  = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;
        return [$user, $token];
    }

    private function makePlan(array $overrides = []): Plan
    {
        return Plan::factory()->create(array_merge([
            'price'        => 100.00,
            'iva_porcentaje' => 16,
            'modalidad_iva' => 'excluded',
            'status'        => 'active',
            'billing_cycles' => ['monthly'],
        ], $overrides));
    }

    public function test_preview_returns_correct_totals_without_discount(): void
    {
        [$user, $token] = $this->makeUserWithToken();
        $plan = $this->makePlan();

        $this->withToken($token)
            ->postJson('/api/checkout/preview', [
                'plan_id'       => $plan->id,
                'billing_cycle' => 'monthly',
            ])
            ->assertStatus(200)
            ->assertJson([
                'subtotal'        => 100.0,
                'iva_percentage'  => 16.0,
                'iva_amount'      => 16.0,
                'discount_amount' => 0.0,
                'total'           => 116.0,
            ]);
    }

    public function test_preview_applies_percentage_discount(): void
    {
        [$user, $token] = $this->makeUserWithToken();
        $plan     = $this->makePlan();
        Discount::factory()->create(['code' => 'SAVE10', 'type' => 'percentage', 'value' => 10, 'status' => 'active']);

        $this->withToken($token)
            ->postJson('/api/checkout/preview', [
                'plan_id'       => $plan->id,
                'billing_cycle' => 'monthly',
                'discount_code' => 'SAVE10',
            ])
            ->assertStatus(200)
            ->assertJson([
                'subtotal'        => 90.0,
                'iva_amount'      => 14.4,
                'discount_amount' => 10.0,
                'total'           => 104.4,
            ]);
    }

    public function test_preview_applies_fixed_discount(): void
    {
        [$user, $token] = $this->makeUserWithToken();
        $plan     = $this->makePlan();
        Discount::factory()->create(['code' => 'FLAT20', 'type' => 'fixed', 'value' => 20, 'status' => 'active']);

        $response = $this->withToken($token)
            ->postJson('/api/checkout/preview', [
                'plan_id'       => $plan->id,
                'billing_cycle' => 'monthly',
                'discount_code' => 'FLAT20',
            ])
            ->assertStatus(200);

        $this->assertEquals(20.0, $response->json('discount_amount'));
        $this->assertEquals(80.0, $response->json('subtotal'));
    }

    public function test_confirm_creates_subscription_and_payment(): void
    {
        [$user, $token] = $this->makeUserWithToken();
        $plan = $this->makePlan();

        $response = $this->withToken($token)
            ->postJson('/api/checkout/confirm', [
                'plan_id'       => $plan->id,
                'billing_cycle' => 'monthly',
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('subscriptions', ['user_id' => $user->id, 'plan_id' => $plan->id]);
        $this->assertDatabaseHas('payments', ['user_id' => $user->id, 'status' => 'approved']);
    }

    public function test_confirm_records_discount_usage(): void
    {
        [$user, $token] = $this->makeUserWithToken();
        $plan     = $this->makePlan();
        $discount = Discount::factory()->create(['code' => 'WELCOME', 'type' => 'percentage', 'value' => 5, 'status' => 'active']);

        $this->withToken($token)
            ->postJson('/api/checkout/confirm', [
                'plan_id'       => $plan->id,
                'billing_cycle' => 'monthly',
                'discount_code' => 'WELCOME',
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('discount_usages', ['discount_id' => $discount->id, 'user_id' => $user->id]);
        $this->assertEquals(1, $discount->fresh()->used_count);
    }

    public function test_confirm_rejects_invalid_discount(): void
    {
        [$user, $token] = $this->makeUserWithToken();
        $plan = $this->makePlan();
        Discount::factory()->create(['code' => 'EXPIRED', 'status' => 'active', 'valid_until' => now()->subDay()]);

        $this->withToken($token)
            ->postJson('/api/checkout/confirm', [
                'plan_id'       => $plan->id,
                'billing_cycle' => 'monthly',
                'discount_code' => 'EXPIRED',
            ])
            ->assertStatus(422);
    }

    public function test_confirm_requires_authentication(): void
    {
        $plan = $this->makePlan();

        $this->postJson('/api/checkout/confirm', [
            'plan_id'       => $plan->id,
            'billing_cycle' => 'monthly',
        ])->assertStatus(401);
    }

    public function test_iva_included_calculation(): void
    {
        [$user, $token] = $this->makeUserWithToken();
        $plan = $this->makePlan(['modalidad_iva' => 'included', 'price' => 116.0]);

        $response = $this->withToken($token)
            ->postJson('/api/checkout/preview', [
                'plan_id'       => $plan->id,
                'billing_cycle' => 'monthly',
            ])
            ->assertStatus(200);

        // 116 price includes IVA: subtotal=100, iva=16, total=116
        $this->assertEquals(100.0, $response->json('subtotal'));
        $this->assertEquals(16.0,  $response->json('iva_amount'));
        $this->assertEquals(116.0, $response->json('total'));
    }
}
