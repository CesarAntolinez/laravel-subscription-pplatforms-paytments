<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_id',
        'provider',
        'currency',
        'subtotal',
        'iva_percentage_applied',
        'iva_amount',
        'total',
        'iva_modality',
        'base_imponible',
        'idempotency_key',
        'billed_at',
        'status',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'iva_percentage_applied' => 'decimal:2',
        'iva_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'base_imponible' => 'decimal:2',
        'billed_at' => 'datetime',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
