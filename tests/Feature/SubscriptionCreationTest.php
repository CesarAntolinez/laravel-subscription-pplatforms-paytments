<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\PlanBillingCycle;
use App\Models\Subscription;
use App\Models\User;
use App\Services\SubscriptionService;
use App\Services\TaxCalculationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class SubscriptionCreationTest extends TestCase
{
    use RefreshDatabase;

    private SubscriptionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SubscriptionService(new TaxCalculationService());
    }

    private function createPlan(array $attributes = []): Plan
    {
        return Plan::create(array_merge([
            'name' => 'Plan Básico',
            'status' => 'active',
            'iva_percentage' => 16,
            'iva_modality' => 'excluded',
            'currency' => 'MXN',
            'decimal_precision' => 2,
            'auto_renew' => true,
        ], $attributes));
    }

    private function addBillingCycle(Plan $plan, string $cycle, float $price): PlanBillingCycle
    {
        return PlanBillingCycle::create([
            'plan_id' => $plan->id,
            'cycle' => $cycle,
            'price' => $price,
            'enabled' => true,
        ]);
    }

    public function test_creates_active_subscription_with_payment(): void
    {
        $user = User::factory()->create();
        $plan = $this->createPlan();
        $this->addBillingCycle($plan, 'monthly', 100.00);

        $subscription = $this->service->create($user->id, $plan, 'monthly');

        $this->assertEquals(Subscription::STATUS_ACTIVE, $subscription->status);
        $this->assertEquals($user->id, $subscription->user_id);
        $this->assertEquals($plan->id, $subscription->plan_id);
        $this->assertEquals('monthly', $subscription->billing_cycle);
        $this->assertNotNull($subscription->next_billing_at);

        $payment = $subscription->payments()->first();
        $this->assertNotNull($payment);
        $this->assertEquals('100.00', $payment->subtotal);
        $this->assertEquals('16.00', $payment->iva_amount);
        $this->assertEquals('116.00', $payment->total);
        $this->assertEquals('excluded', $payment->iva_modality);
    }

    public function test_creates_trial_subscription_when_plan_has_trial_days(): void
    {
        $user = User::factory()->create();
        $plan = $this->createPlan(['trial_days' => 14]);
        $this->addBillingCycle($plan, 'monthly', 100.00);

        $subscription = $this->service->create($user->id, $plan, 'monthly');

        $this->assertEquals(Subscription::STATUS_TRIAL, $subscription->status);
        $this->assertNotNull($subscription->trial_ends_at);
        $this->assertTrue($subscription->trial_ends_at->greaterThan($subscription->starts_at));
        // No payment recorded during trial
        $this->assertEquals(0, $subscription->payments()->count());
    }

    public function test_throws_exception_for_disabled_billing_cycle(): void
    {
        $user = User::factory()->create();
        $plan = $this->createPlan();
        $this->addBillingCycle($plan, 'monthly', 100.00);

        $this->expectException(InvalidArgumentException::class);
        $this->service->create($user->id, $plan, 'annual');
    }

    public function test_calculates_iva_included_correctly(): void
    {
        $user = User::factory()->create();
        $plan = $this->createPlan(['iva_modality' => 'included', 'iva_percentage' => 16]);
        $this->addBillingCycle($plan, 'monthly', 116.00);

        $subscription = $this->service->create($user->id, $plan, 'monthly');

        $payment = $subscription->payments()->first();
        $this->assertEquals('100.00', $payment->subtotal);
        $this->assertEquals('16.00', $payment->iva_amount);
        $this->assertEquals('116.00', $payment->total);
        $this->assertEquals('included', $payment->iva_modality);
    }

    public function test_next_billing_date_is_one_month_ahead_for_monthly_cycle(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 1, 1, 0, 0, 0));

        $user = User::factory()->create();
        $plan = $this->createPlan();
        $this->addBillingCycle($plan, 'monthly', 100.00);

        $subscription = $this->service->create($user->id, $plan, 'monthly');

        $this->assertEquals('2026-02-01', $subscription->next_billing_at->toDateString());

        Carbon::setTestNow();
    }

    public function test_quarterly_billing_cycle_advances_three_months(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 1, 1, 0, 0, 0));

        $user = User::factory()->create();
        $plan = $this->createPlan();
        $this->addBillingCycle($plan, 'quarterly', 270.00);

        $subscription = $this->service->create($user->id, $plan, 'quarterly');

        $this->assertEquals('2026-04-01', $subscription->next_billing_at->toDateString());

        Carbon::setTestNow();
    }
}
