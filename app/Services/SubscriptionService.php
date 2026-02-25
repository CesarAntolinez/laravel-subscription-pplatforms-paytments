<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Str;
use InvalidArgumentException;

class SubscriptionService
{
    public function __construct(
        private readonly TaxCalculationService $taxService
    ) {}

    /**
     * Create a new subscription for a user on a plan with a given billing cycle.
     *
     * @param  int    $userId
     * @param  Plan   $plan
     * @param  string $billingCycle  'monthly', 'quarterly', 'annual'
     * @return Subscription
     *
     * @throws InvalidArgumentException
     */
    public function create(int $userId, Plan $plan, string $billingCycle): Subscription
    {
        if (! $plan->hasBillingCycle($billingCycle)) {
            throw new InvalidArgumentException(
                "El ciclo de facturación '{$billingCycle}' no está habilitado para el plan '{$plan->name}'."
            );
        }

        $now = Carbon::now();
        $trialEndsAt = null;
        $status = Subscription::STATUS_ACTIVE;

        if ($plan->trial_days > 0) {
            $trialEndsAt = $now->copy()->addDays($plan->trial_days);
            $status = Subscription::STATUS_TRIAL;
        }

        $nextBillingAt = $this->calculateNextBillingDate($now, $billingCycle);

        $subscription = Subscription::create([
            'user_id' => $userId,
            'plan_id' => $plan->id,
            'billing_cycle' => $billingCycle,
            'status' => $status,
            'starts_at' => $now,
            'trial_ends_at' => $trialEndsAt,
            'next_billing_at' => $nextBillingAt,
        ]);

        if ($status === Subscription::STATUS_ACTIVE) {
            $this->recordPayment($subscription, $plan, $billingCycle, $now);
        }

        return $subscription;
    }

    /**
     * Renew an active subscription.
     *
     * @param  Subscription $subscription
     * @return Payment
     *
     * @throws InvalidArgumentException
     */
    public function renew(Subscription $subscription): Payment
    {
        if (! in_array($subscription->status, [Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIAL])) {
            throw new InvalidArgumentException(
                "No se puede renovar una suscripción con estado '{$subscription->status}'."
            );
        }

        $now = Carbon::now();
        $plan = $subscription->plan;

        $payment = $this->recordPayment($subscription, $plan, $subscription->billing_cycle, $now);

        $subscription->update([
            'status' => Subscription::STATUS_ACTIVE,
            'next_billing_at' => $this->calculateNextBillingDate($now, $subscription->billing_cycle),
        ]);

        return $payment;
    }

    /**
     * Calculate the next billing date based on billing cycle.
     */
    public function calculateNextBillingDate(Carbon $from, string $billingCycle): Carbon
    {
        return match ($billingCycle) {
            'monthly' => $from->copy()->addMonth(),
            'quarterly' => $from->copy()->addMonths(3),
            'annual' => $from->copy()->addYear(),
            default => throw new InvalidArgumentException("Ciclo de facturación inválido: '{$billingCycle}'."),
        };
    }

    /**
     * Record a payment for a subscription.
     */
    private function recordPayment(
        Subscription $subscription,
        Plan $plan,
        string $billingCycle,
        Carbon $billedAt
    ): Payment {
        $billingCycleModel = $plan->enabledBillingCycles()->where('cycle', $billingCycle)->firstOrFail();

        $breakdown = $this->taxService->calculate(
            (float) $billingCycleModel->price,
            (float) $plan->iva_percentage,
            $plan->iva_modality,
            $plan->decimal_precision
        );

        return Payment::create([
            'subscription_id' => $subscription->id,
            'provider' => 'manual',
            'currency' => $plan->currency,
            'subtotal' => $breakdown['subtotal'],
            'iva_percentage_applied' => $plan->iva_percentage,
            'iva_amount' => $breakdown['iva_amount'],
            'total' => $breakdown['total'],
            'iva_modality' => $plan->iva_modality,
            'base_imponible' => $breakdown['base_imponible'],
            'idempotency_key' => (string) Str::uuid(),
            'billed_at' => $billedAt,
            'status' => 'paid',
        ]);
    }
}
