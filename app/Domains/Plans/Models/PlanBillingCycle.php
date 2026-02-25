<?php

namespace App\Domains\Plans\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanBillingCycle extends Model
{
    use HasFactory;

    const CYCLE_MONTHLY = 'monthly';
    const CYCLE_QUARTERLY = 'quarterly';
    const CYCLE_ANNUAL = 'annual';

    protected $fillable = [
        'plan_id',
        'cycle',
        'interval_days',
        'price_modifier',
        'is_active',
    ];

    protected $casts = [
        'interval_days' => 'integer',
        'price_modifier' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
}
