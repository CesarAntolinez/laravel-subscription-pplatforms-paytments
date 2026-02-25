<?php

namespace App\Domains\Plans\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'status',
        'auto_renewal',
        'trial_days',
        'iva_percentage',
        'iva_mode',
        'currency',
        'decimal_precision',
    ];

    protected $casts = [
        'auto_renewal' => 'boolean',
        'trial_days' => 'integer',
        'iva_percentage' => 'decimal:2',
        'decimal_precision' => 'integer',
    ];

    public function levels()
    {
        return $this->hasMany(PlanLevel::class)->orderBy('sort_order');
    }

    public function billingCycles()
    {
        return $this->hasMany(PlanBillingCycle::class);
    }

    public function activeBillingCycles()
    {
        return $this->hasMany(PlanBillingCycle::class)->where('is_active', true);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($plan) {
            if (empty($plan->slug)) {
                $plan->slug = \Illuminate\Support\Str::slug($plan->name);
            }
        });
    }
}
