<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'idempotency_key',
        'user_id',
        'subscription_id',
        'provider',
        'provider_transaction_id',
        'currency',
        'subtotal',
        'vat_rate',
        'vat_amount',
        'total',
        'vat_mode',
        'status',
        'description',
        'metadata',
        'charged_at',
    ];

    protected $casts = [
        'metadata'    => 'array',
        'charged_at'  => 'datetime',
        'subtotal'    => 'decimal:4',
        'vat_rate'    => 'decimal:4',
        'vat_amount'  => 'decimal:4',
        'total'       => 'decimal:4',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function retries(): HasMany
    {
        return $this->hasMany(PaymentRetry::class);
    }

    /**
     * Whether the transaction has already been successfully charged.
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}
