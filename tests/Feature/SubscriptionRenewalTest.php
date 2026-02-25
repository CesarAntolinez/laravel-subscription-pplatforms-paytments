<?php

namespace Tests\Feature;

use App\Jobs\ProcessSubscriptionRenewalsJob;
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

class SubscriptionRenewalTest extends TestCase
{
    use RefreshDatabase;

    private SubscriptionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SubscriptionService(new TaxCalculationService());
    }

    private function createActiveSubscription(
        string $billingCycle = 'monthly',
        Carbon $nextBillingAt = null
    ): Subscription {
        $user = User::factory()->create();
        $plan = Plan::create([
            'name' => 'Plan ' . uniqid(),
            'status' => 'active',
            'iva_percentage' => 16,
            'iva_modality' => 'excluded',
            'currency' => 'MXN',
            'decimal_precision' => 2,
            'auto_renew' => true,
        ]);
        PlanBillingCycle::create([
            'plan_id' => $plan->id,
            'cycle' => $billingCycle,
            'price' => 100.00,
            'enabled' => true,
        ]);

        return Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'billing_cycle' => $billingCycle,
            'status' => Subscription::STATUS_ACTIVE,
            'starts_at' => Carbon::now()->subMonth(),
            'next_billing_at' => $nextBillingAt ?? Carbon::now()->subHour(),
        ]);
    }

    public function test_renews_active_subscription_and_creates_payment(): void
    {
        $subscription = $this->createActiveSubscription();
        $previousNextBilling = $subscription->next_billing_at;

        $payment = $this->service->renew($subscription);

        $this->assertEquals('paid', $payment->status);
        $this->assertEquals('116.00', $payment->total);
        $this->assertEquals('excluded', $payment->iva_modality);

        $subscription->refresh();
        $this->assertEquals(Subscription::STATUS_ACTIVE, $subscription->status);
        $this->assertTrue($subscription->next_billing_at->greaterThan($previousNextBilling));
    }

    public function test_renewal_advances_next_billing_by_one_month(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 1));

        $subscription = $this->createActiveSubscription('monthly', Carbon::now());

        $this->service->renew($subscription);

        $subscription->refresh();
        $this->assertEquals('2026-03-01', $subscription->next_billing_at->toDateString());

        Carbon::setTestNow();
    }

    public function test_cannot_renew_cancelled_subscription(): void
    {
        $subscription = $this->createActiveSubscription();
        $subscription->update(['status' => Subscription::STATUS_CANCELLED]);

        $this->expectException(InvalidArgumentException::class);
        $this->service->renew($subscription);
    }

    public function test_process_renewals_job_renews_due_subscriptions(): void
    {
        $due = $this->createActiveSubscription('monthly', Carbon::now()->subMinute());
        $notDue = $this->createActiveSubscription('monthly', Carbon::now()->addDay());

        $job = new ProcessSubscriptionRenewalsJob();
        $job->handle($this->service);

        $due->refresh();
        $notDue->refresh();

        // Due subscription gets a new next_billing_at in the future
        $this->assertTrue($due->next_billing_at->greaterThan(Carbon::now()));
        // Not-due subscription remains unchanged
        $this->assertTrue($notDue->next_billing_at->greaterThan(Carbon::now()));
        $this->assertEquals(0, $notDue->payments()->count());
    }

    public function test_process_renewals_job_does_not_renew_not_due_subscriptions(): void
    {
        $notDue = $this->createActiveSubscription('monthly', Carbon::now()->addWeek());

        $job = new ProcessSubscriptionRenewalsJob();
        $job->handle($this->service);

        $this->assertEquals(0, $notDue->payments()->count());
    }
}
