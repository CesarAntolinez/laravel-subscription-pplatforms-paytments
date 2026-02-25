<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'status',
        'trial_days',
        'auto_renew',
        'iva_percentage',
        'iva_modality',
        'currency',
        'decimal_precision',
    ];

    protected $casts = [
        'auto_renew' => 'boolean',
        'trial_days' => 'integer',
        'iva_percentage' => 'decimal:2',
        'decimal_precision' => 'integer',
    ];

    public function billingCycles(): HasMany
    {
        return $this->hasMany(PlanBillingCycle::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function enabledBillingCycles(): HasMany
    {
        return $this->hasMany(PlanBillingCycle::class)->where('enabled', true);
    }

    public function hasBillingCycle(string $cycle): bool
    {
        return $this->enabledBillingCycles()->where('cycle', $cycle)->exists();
    }
}
