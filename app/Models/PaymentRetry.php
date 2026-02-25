<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentRetry extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'attempt_number',
        'status',
        'failure_reason',
        'scheduled_at',
        'executed_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'executed_at'  => 'datetime',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
