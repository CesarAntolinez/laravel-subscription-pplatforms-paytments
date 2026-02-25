<?php

namespace App\Domains\Plans\Services;

use App\Domains\Audit\Services\AuditService;
use App\Domains\Plans\Models\Plan;
use App\Domains\Plans\Models\PlanBillingCycle;
use App\Domains\Plans\Models\PlanLevel;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class PlanService
{
    public function __construct(private AuditService $auditService) {}

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Plan::query();

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('description', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->with(['levels', 'billingCycles'])->paginate($perPage);
    }

    public function create(array $data): Plan
    {
        $data['slug'] = Str::slug($data['name']);

        $levels = $data['levels'] ?? [];
        $cycles = $data['billing_cycles'] ?? [];
        unset($data['levels'], $data['billing_cycles']);

        $plan = Plan::create($data);

        foreach ($levels as $level) {
            $plan->levels()->create($level);
        }

        foreach ($cycles as $cycle) {
            $plan->billingCycles()->create($cycle);
        }

        $this->auditService->log('created', Plan::class, $plan->id, [], $plan->toArray());

        return $plan->load(['levels', 'billingCycles']);
    }

    public function update(Plan $plan, array $data): Plan
    {
        $oldValues = $plan->toArray();

        if (!empty($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $levels = $data['levels'] ?? null;
        $cycles = $data['billing_cycles'] ?? null;
        unset($data['levels'], $data['billing_cycles']);

        $plan->update($data);

        if ($levels !== null) {
            $plan->levels()->delete();
            foreach ($levels as $level) {
                $plan->levels()->create($level);
            }
        }

        if ($cycles !== null) {
            $plan->billingCycles()->delete();
            foreach ($cycles as $cycle) {
                $plan->billingCycles()->create($cycle);
            }
        }

        $this->auditService->log('updated', Plan::class, $plan->id, $oldValues, $plan->fresh()->toArray());

        return $plan->load(['levels', 'billingCycles']);
    }

    public function delete(Plan $plan): void
    {
        $this->auditService->log('deleted', Plan::class, $plan->id, $plan->toArray(), []);
        $plan->delete();
    }

    public function toggleStatus(Plan $plan): Plan
    {
        $oldStatus = $plan->status;
        $newStatus = $plan->status === 'active' ? 'inactive' : 'active';
        $plan->update(['status' => $newStatus]);

        $this->auditService->log(
            'status_changed',
            Plan::class,
            $plan->id,
            ['status' => $oldStatus],
            ['status' => $newStatus]
        );

        return $plan;
    }
}
