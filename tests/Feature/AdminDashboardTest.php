<?php

namespace Tests\Feature;

use App\Models\Discount;
use App\Models\DiscountUsage;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * HU-F1 — Admin dashboard metrics
 * HU-E3, HU-F2 — Reports
 */
class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function seedFixtures(): array
    {
        $user  = User::factory()->create();
        $plan  = Plan::factory()->create(['price' => 100, 'iva_porcentaje' => 16, 'modalidad_iva' => 'excluded']);
        $sub   = Subscription::factory()->create(['user_id' => $user->id, 'plan_id' => $plan->id, 'status' => 'active', 'starts_at' => now()]);
        $payment = Payment::factory()->create([
            'subscription_id' => $sub->id,
            'user_id'         => $user->id,
            'status'          => 'approved',
            'total'           => 116,
            'iva_amount'      => 16,
            'subtotal'        => 100,
            'charged_at'      => now(),
            'idempotency_key' => (string) Str::uuid(),
        ]);
        $discount = Discount::factory()->create(['code' => 'DASH10', 'type' => 'percentage', 'value' => 10]);
        DiscountUsage::factory()->create([
            'discount_id'      => $discount->id,
            'user_id'          => $user->id,
            'plan_id'          => $plan->id,
            'subscription_id'  => $sub->id,
            'payment_id'       => $payment->id,
            'amount_discounted' => 10,
            'applied_at'       => now(),
        ]);

        return compact('user', 'plan', 'sub', 'payment', 'discount');
    }

    public function test_dashboard_returns_expected_shape(): void
    {
        $this->seedFixtures();

        $response = $this->getJson('/api/admin/dashboard')
            ->assertStatus(200);

        $response->assertJsonStructure([
            'period'        => ['from', 'to'],
            'subscriptions' => ['active', 'total', 'cancelled'],
            'revenue'       => ['subtotal', 'iva', 'total'],
            'payments'      => ['failed'],
            'discounts'     => ['applied', 'total_discounted'],
            'users'         => ['new'],
        ]);
    }

    public function test_dashboard_revenue_totals_are_correct(): void
    {
        $this->seedFixtures();

        $response = $this->getJson('/api/admin/dashboard')->assertStatus(200);

        $this->assertEquals(116.0, $response->json('revenue.total'));
        $this->assertEquals(16.0,  $response->json('revenue.iva'));
    }

    public function test_dashboard_discount_metrics(): void
    {
        $this->seedFixtures();

        $response = $this->getJson('/api/admin/dashboard')->assertStatus(200);

        $this->assertEquals(1,    $response->json('discounts.applied'));
        $this->assertEquals(10.0, $response->json('discounts.total_discounted'));
    }

    public function test_discount_usage_report(): void
    {
        $data = $this->seedFixtures();

        $this->getJson('/api/reports/discount-usage')
            ->assertStatus(200)
            ->assertJsonStructure(['summary' => ['total_usages', 'total_discounted'], 'data']);
    }

    public function test_failed_payments_report(): void
    {
        $data = $this->seedFixtures();

        // Create a failed payment
        Payment::factory()->create([
            'subscription_id' => $data['sub']->id,
            'user_id'         => $data['user']->id,
            'status'          => 'failed',
            'total'           => 116,
            'iva_amount'      => 16,
            'subtotal'        => 100,
            'idempotency_key' => (string) Str::uuid(),
        ]);

        $response = $this->getJson('/api/reports/failed-payments')
            ->assertStatus(200)
            ->assertJsonStructure(['summary' => ['total_failed', 'total_amount'], 'data']);

        $this->assertEquals(1, $response->json('summary.total_failed'));
    }
}
