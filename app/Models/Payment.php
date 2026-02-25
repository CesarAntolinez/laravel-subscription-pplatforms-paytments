<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_id', 'user_id', 'provider', 'currency',
        'subtotal', 'iva_percentage', 'iva_amount', 'total',
        'modalidad_iva', 'status', 'idempotency_key',
        'provider_transaction_id', 'attempt_count', 'metadata',
        'charged_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'charged_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'iva_percentage' => 'decimal:2',
        'iva_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function discountUsage()
    {
        return $this->hasOne(DiscountUsage::class);
    }
}
