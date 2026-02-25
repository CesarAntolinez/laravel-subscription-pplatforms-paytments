<?php

namespace App\Http\Controllers;

use App\Models\Discount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * RF-DES-001, RF-DES-004 — Discount CRUD (HU-E1)
 */
class DiscountController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Discount::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('code')) {
            $query->where('code', 'like', '%' . $request->code . '%');
        }

        $discounts = $query->orderByDesc('created_at')->paginate(20);

        return response()->json($discounts);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code'                  => 'required|string|max:64|unique:discounts,code',
            'name'                  => 'required|string|max:255',
            'type'                  => ['required', Rule::in(['percentage', 'fixed', 'free_trial'])],
            'value'                 => 'required|numeric|min:0',
            'max_uses'              => 'nullable|integer|min:1',
            'valid_from'            => 'nullable|date',
            'valid_until'           => 'nullable|date|after_or_equal:valid_from',
            'restrict_plan_id'      => 'nullable|exists:plans,id',
            'restrict_user_segment' => 'nullable|string|max:64',
            'restrict_signup_type'  => ['nullable', Rule::in(['new', 'returning'])],
        ]);

        $validated['status'] = 'active';
        $validated['used_count'] = 0;

        $discount = Discount::create($validated);

        return response()->json($discount, 201);
    }

    public function show(Discount $discount): JsonResponse
    {
        return response()->json($discount->load('restrictedPlan'));
    }

    public function update(Request $request, Discount $discount): JsonResponse
    {
        $validated = $request->validate([
            'code'                  => ['sometimes', 'string', 'max:64', Rule::unique('discounts', 'code')->ignore($discount->id)],
            'name'                  => 'sometimes|string|max:255',
            'type'                  => ['sometimes', Rule::in(['percentage', 'fixed', 'free_trial'])],
            'value'                 => 'sometimes|numeric|min:0',
            'max_uses'              => 'nullable|integer|min:1',
            'valid_from'            => 'nullable|date',
            'valid_until'           => 'nullable|date|after_or_equal:valid_from',
            'status'                => ['sometimes', Rule::in(['active', 'paused'])],
            'restrict_plan_id'      => 'nullable|exists:plans,id',
            'restrict_user_segment' => 'nullable|string|max:64',
            'restrict_signup_type'  => ['nullable', Rule::in(['new', 'returning'])],
        ]);

        $discount->update($validated);

        return response()->json($discount);
    }

    public function destroy(Discount $discount): JsonResponse
    {
        // RF-DES-004: logical delete (soft delete + status=deleted)
        $discount->update(['status' => 'deleted']);
        $discount->delete();

        return response()->json(['message' => 'Discount deleted.']);
    }

    /**
     * RF-DES-002 — Validate a discount code without applying it.
     */
    public function validateCode(Request $request): JsonResponse
    {
        $request->validate([
            'code'    => 'required|string',
            'plan_id' => 'nullable|exists:plans,id',
        ]);

        $discount = Discount::where('code', $request->code)->first();

        if (!$discount) {
            return response()->json(['valid' => false, 'reason' => 'Code not found.'], 404);
        }

        if (!$discount->isValid()) {
            $reason = $this->invalidReason($discount);
            return response()->json(['valid' => false, 'reason' => $reason], 422);
        }

        // RN-DES-004: plan restriction check
        if ($discount->restrict_plan_id && $request->filled('plan_id') && $discount->restrict_plan_id != $request->plan_id) {
            return response()->json(['valid' => false, 'reason' => 'Code not applicable to the selected plan.'], 422);
        }

        return response()->json([
            'valid'    => true,
            'discount' => $discount->only(['id', 'code', 'name', 'type', 'value']),
        ]);
    }

    private function invalidReason(Discount $discount): string
    {
        if ($discount->status === 'paused') {
            return 'Discount is paused.';
        }
        if ($discount->status === 'deleted') {
            return 'Discount has been removed.';
        }
        $now = now();
        if ($discount->valid_from && $now->lt($discount->valid_from)) {
            return 'Discount is not yet valid.';
        }
        if ($discount->valid_until && $now->gt($discount->valid_until)) {
            return 'Discount has expired.';
        }
        if ($discount->max_uses !== null && $discount->used_count >= $discount->max_uses) {
            return 'Discount usage limit reached.';
        }
        return 'Discount is invalid.';
    }
}
