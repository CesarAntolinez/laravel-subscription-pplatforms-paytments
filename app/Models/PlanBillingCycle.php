<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanBillingCycle extends Model
{
    use HasFactory;

    protected $fillable = [
        'plan_id',
        'cycle',
        'price',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'price' => 'decimal:2',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
