<?php

namespace App\Http\Controllers;

use App\Models\Discount;
use App\Models\DiscountUsage;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * RF-DES-002, RF-DES-003 — Apply discount code during checkout (HU-E2)
 */
class CheckoutController extends Controller
{
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

        [$subtotal, $ivaAmount, $total, $discountAmount, $discount] =
            $this->calculateAmounts($plan, $validated['discount_code'] ?? null, $request->user());

        return response()->json([
            'plan_id'          => $plan->id,
            'billing_cycle'    => $validated['billing_cycle'],
            'subtotal'         => $subtotal,
            'iva_percentage'   => (float) $plan->iva_porcentaje,
            'iva_amount'       => $ivaAmount,
            'discount_amount'  => $discountAmount,
            'total'            => $total,
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
            $discount = $this->resolveDiscount($validated['discount_code'], $plan->id);
            if (is_array($discount)) {
                // error response
                return response()->json($discount, 422);
            }
        }

        [$subtotal, $ivaAmount, $total, $discountAmount] =
            $this->calculateAmounts($plan, $validated['discount_code'] ?? null, $user);

        // Create subscription
        $subscription = Subscription::create([
            'user_id'       => $user->id,
            'plan_id'       => $plan->id,
            'status'        => 'active',
            'billing_cycle' => $validated['billing_cycle'],
            'starts_at'     => now(),
            'next_billing_at' => $this->nextBillingDate($validated['billing_cycle']),
            'discount_code' => $discount ? $discount->code : null,
        ]);

        // Create payment record
        $payment = Payment::create([
            'subscription_id'      => $subscription->id,
            'user_id'              => $user->id,
            'provider'             => $validated['provider'] ?? 'stripe',
            'currency'             => $plan->currency,
            'subtotal'             => $subtotal,
            'iva_percentage'       => (float) $plan->iva_porcentaje,
            'iva_amount'           => $ivaAmount,
            'total'                => $total,
            'modalidad_iva'        => $plan->modalidad_iva,
            'status'               => 'approved',
            'idempotency_key'      => (string) Str::uuid(),
            'attempt_count'        => 1,
            'charged_at'           => now(),
        ]);

        // Record discount usage (RF-DES-005)
        if ($discount) {
            DiscountUsage::create([
                'discount_id'      => $discount->id,
                'user_id'          => $user->id,
                'plan_id'          => $plan->id,
                'subscription_id'  => $subscription->id,
                'payment_id'       => $payment->id,
                'amount_discounted' => $discountAmount,
                'applied_at'       => now(),
            ]);

            // Increment usage counter
            $discount->increment('used_count');
        }

        return response()->json([
            'subscription' => $subscription->load('plan'),
            'payment'      => $payment,
            'discount_usage' => $discount ? [
                'code'             => $discount->code,
                'amount_discounted' => $discountAmount,
            ] : null,
        ], 201);
    }

    // -----------------------------------------------------------------

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

    private function calculateAmounts(Plan $plan, ?string $code, $user): array
    {
        $basePrice = (float) $plan->price;

        $discount = null;
        $discountAmount = 0.0;

        if ($code) {
            $d = Discount::where('code', $code)->first();
            if ($d && $d->isValid()) {
                $discount = $d;
                $discountAmount = $d->calculateDiscount($basePrice);
            }
        }

        $priceAfterDiscount = max(0, $basePrice - $discountAmount);

        // IVA calculation respecting modalidad_iva
        $ivaRate = (float) $plan->iva_porcentaje / 100;
        if ($plan->modalidad_iva === 'included') {
            // Price already includes IVA; extract it
            $subtotal  = round($priceAfterDiscount / (1 + $ivaRate), 2);
            $ivaAmount = round($priceAfterDiscount - $subtotal, 2);
            $total     = $priceAfterDiscount;
        } else {
            $subtotal  = $priceAfterDiscount;
            $ivaAmount = round($priceAfterDiscount * $ivaRate, 2);
            $total     = round($priceAfterDiscount + $ivaAmount, 2);
        }

        return [$subtotal, $ivaAmount, $total, $discountAmount, $discount];
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
