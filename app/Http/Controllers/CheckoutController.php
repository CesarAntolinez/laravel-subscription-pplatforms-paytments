<?php

namespace App\Http\Controllers;

use App\Models\Discount;
use App\Models\DiscountUsage;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\TaxCalculationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * RF-DES-002, RF-DES-003 — Apply discount code during checkout (HU-E2)
 */
class CheckoutController extends Controller
{
    public function __construct(private readonly TaxCalculationService $taxService) {}

    /**
     * Preview checkout total for a plan, optionally with a discount code.
     * Returns subtotal, IVA breakdown and total.
     */
    public function preview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plan_id'       => 'required|exists:plans,id',
            'billing_cycle' => 'required|string',
            'discount_code' => 'nullable|string',
        ]);

        $plan = Plan::findOrFail($validated['plan_id']);
        $cyclePrice = $this->cyclePrice($plan, $validated['billing_cycle']);

        [$discountAmount, $discount] = $this->resolveDiscountAmount($validated['discount_code'] ?? null, $cyclePrice, $plan->id);

        $priceAfterDiscount = max(0, $cyclePrice - $discountAmount);
        $tax = $this->taxService->calculate(
            $priceAfterDiscount,
            (float) $plan->iva_percentage,
            $plan->iva_modality,
            (int) $plan->decimal_precision
        );

        return response()->json([
            'plan_id'          => $plan->id,
            'billing_cycle'    => $validated['billing_cycle'],
            'subtotal'         => $tax['subtotal'],
            'iva_percentage'   => (float) $plan->iva_percentage,
            'iva_amount'       => $tax['iva_amount'],
            'discount_amount'  => $discountAmount,
            'total'            => $tax['total'],
            'discount_applied' => $discount ? $discount->only(['id', 'code', 'name', 'type', 'value']) : null,
        ]);
    }

    /**
     * Confirm checkout: create subscription + payment record and consume discount.
     * RN-DES-001: only one discount per subscription.
     */
    public function confirm(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plan_id'       => 'required|exists:plans,id',
            'billing_cycle' => 'required|string',
            'discount_code' => 'nullable|string',
            'provider'      => 'nullable|string|in:stripe,mercadopago',
        ]);

        $plan = Plan::findOrFail($validated['plan_id']);
        $user = $request->user();

        // Validate discount if provided
        $discount = null;
        if (!empty($validated['discount_code'])) {
            $result = $this->resolveDiscount($validated['discount_code'], $plan->id);
            if (is_array($result)) {
                return response()->json($result, 422);
            }
            $discount = $result;
        }

        $cyclePrice = $this->cyclePrice($plan, $validated['billing_cycle']);
        $discountAmount = $discount ? $discount->calculateDiscount($cyclePrice) : 0.0;
        $priceAfterDiscount = max(0, $cyclePrice - $discountAmount);

        $tax = $this->taxService->calculate(
            $priceAfterDiscount,
            (float) $plan->iva_percentage,
            $plan->iva_modality,
            (int) $plan->decimal_precision
        );

        // Create subscription
        $subscription = Subscription::create([
            'user_id'         => $user->id,
            'plan_id'         => $plan->id,
            'status'          => 'active',
            'billing_cycle'   => $validated['billing_cycle'],
            'starts_at'       => now(),
            'next_billing_at' => $this->nextBillingDate($validated['billing_cycle']),
            'discount_code'   => $discount ? $discount->code : null,
        ]);

        // Create payment record using main's Payment schema
        $payment = Payment::create([
            'subscription_id'        => $subscription->id,
            'provider'               => $validated['provider'] ?? 'stripe',
            'currency'               => $plan->currency,
            'subtotal'               => $tax['subtotal'],
            'iva_percentage_applied' => (float) $plan->iva_percentage,
            'iva_amount'             => $tax['iva_amount'],
            'total'                  => $tax['total'],
            'iva_modality'           => $plan->iva_modality,
            'base_imponible'         => $tax['base_imponible'],
            'idempotency_key'        => (string) Str::uuid(),
            'billed_at'              => now(),
            'status'                 => 'paid',
        ]);

        // Record discount usage (RF-DES-005)
        if ($discount) {
            DiscountUsage::create([
                'discount_id'       => $discount->id,
                'user_id'           => $user->id,
                'plan_id'           => $plan->id,
                'subscription_id'   => $subscription->id,
                'payment_id'        => $payment->id,
                'amount_discounted' => $discountAmount,
                'applied_at'        => now(),
            ]);

            $discount->increment('used_count');
        }

        return response()->json([
            'subscription'   => $subscription->load('plan'),
            'payment'        => $payment,
            'discount_usage' => $discount ? [
                'code'              => $discount->code,
                'amount_discounted' => $discountAmount,
            ] : null,
        ], 201);
    }

    // -----------------------------------------------------------------

    private function cyclePrice(Plan $plan, string $cycle): float
    {
        // Use the billing cycle price if available, otherwise fall back to plan base price
        $billingCycle = $plan->billingCycles()->where('cycle', $cycle)->first();
        if ($billingCycle && isset($billingCycle->price)) {
            return (float) $billingCycle->price;
        }
        // Fallback: use plan price if it exists (for simpler schemas)
        return isset($plan->price) ? (float) $plan->price : 0.0;
    }

    private function resolveDiscount(string $code, int $planId): Discount|array
    {
        $discount = Discount::where('code', $code)->first();

        if (!$discount) {
            return ['error' => 'Discount code not found.'];
        }
        if (!$discount->isValid()) {
            return ['error' => 'Discount code is expired, paused, or exhausted.'];
        }
        if ($discount->restrict_plan_id && $discount->restrict_plan_id != $planId) {
            return ['error' => 'Discount code is not applicable to the selected plan.'];
        }

        return $discount;
    }

    private function resolveDiscountAmount(?string $code, float $price, int $planId): array
    {
        if (!$code) {
            return [0.0, null];
        }
        $discount = Discount::where('code', $code)->first();
        if (!$discount || !$discount->isValid()) {
            return [0.0, null];
        }
        if ($discount->restrict_plan_id && $discount->restrict_plan_id != $planId) {
            return [0.0, null];
        }
        return [$discount->calculateDiscount($price), $discount];
    }

    private function nextBillingDate(string $cycle): \Carbon\Carbon
    {
        return match ($cycle) {
            'annual'    => now()->addYear(),
            'quarterly' => now()->addMonths(3),
            default     => now()->addMonth(),
        };
    }
}
