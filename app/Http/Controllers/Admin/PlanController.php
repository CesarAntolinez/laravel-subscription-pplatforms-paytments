<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Plans\Models\Plan;
use App\Domains\Plans\Services\PlanService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function __construct(private PlanService $planService)
    {
        $this->authorizeResource(Plan::class, 'plan');
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'search']);
        $plans = $this->planService->paginate($filters);

        return response()->json($plans);
    }

    public function show(Plan $plan): JsonResponse
    {
        return response()->json($plan->load(['levels', 'billingCycles']));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'                       => 'required|string|max:255|unique:plans',
            'description'                => 'nullable|string',
            'status'                     => 'in:active,inactive',
            'auto_renewal'               => 'boolean',
            'trial_days'                 => 'integer|min:0',
            'iva_percentage'             => 'numeric|min:0|max:100',
            'iva_mode'                   => 'in:included,excluded',
            'currency'                   => 'string|size:3',
            'decimal_precision'          => 'integer|min:0|max:4',
            'levels'                     => 'array',
            'levels.*.name'              => 'required_with:levels|string|max:255',
            'levels.*.description'       => 'nullable|string',
            'levels.*.price'             => 'required_with:levels|numeric|min:0',
            'levels.*.sort_order'        => 'integer|min:0',
            'levels.*.is_active'         => 'boolean',
            'billing_cycles'             => 'array',
            'billing_cycles.*.cycle'     => 'required_with:billing_cycles|in:monthly,quarterly,annual',
            'billing_cycles.*.interval_days'   => 'required_with:billing_cycles|integer|min:1',
            'billing_cycles.*.price_modifier'  => 'numeric|min:0',
            'billing_cycles.*.is_active'       => 'boolean',
        ]);

        $plan = $this->planService->create($validated);

        return response()->json($plan, 201);
    }

    public function update(Request $request, Plan $plan): JsonResponse
    {
        $validated = $request->validate([
            'name'                       => 'sometimes|string|max:255|unique:plans,name,' . $plan->id,
            'description'                => 'nullable|string',
            'status'                     => 'in:active,inactive',
            'auto_renewal'               => 'boolean',
            'trial_days'                 => 'integer|min:0',
            'iva_percentage'             => 'numeric|min:0|max:100',
            'iva_mode'                   => 'in:included,excluded',
            'currency'                   => 'string|size:3',
            'decimal_precision'          => 'integer|min:0|max:4',
            'levels'                     => 'array',
            'levels.*.name'              => 'required_with:levels|string|max:255',
            'levels.*.description'       => 'nullable|string',
            'levels.*.price'             => 'required_with:levels|numeric|min:0',
            'levels.*.sort_order'        => 'integer|min:0',
            'levels.*.is_active'         => 'boolean',
            'billing_cycles'             => 'array',
            'billing_cycles.*.cycle'     => 'required_with:billing_cycles|in:monthly,quarterly,annual',
            'billing_cycles.*.interval_days'   => 'required_with:billing_cycles|integer|min:1',
            'billing_cycles.*.price_modifier'  => 'numeric|min:0',
            'billing_cycles.*.is_active'       => 'boolean',
        ]);

        $plan = $this->planService->update($plan, $validated);

        return response()->json($plan);
    }

    public function destroy(Plan $plan): JsonResponse
    {
        $this->planService->delete($plan);

        return response()->json(['message' => 'Plan eliminado correctamente.']);
    }

    public function toggleStatus(Plan $plan): JsonResponse
    {
        $this->authorize('update', $plan);
        $plan = $this->planService->toggleStatus($plan);

        return response()->json($plan);
    }
}
