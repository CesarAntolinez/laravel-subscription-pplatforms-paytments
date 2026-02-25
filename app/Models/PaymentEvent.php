<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'event_id',
        'event_type',
        'provider_transaction_id',
        'payload',
        'processed',
    ];

    protected $casts = [
        'payload'   => 'array',
        'processed' => 'boolean',
    ];

    /**
     * Check whether an identical event was already stored (duplicate guard).
     */
    public static function alreadyProcessed(string $provider, string $eventId): bool
    {
        return static::where('provider', $provider)
            ->where('event_id', $eventId)
            ->where('processed', true)
            ->exists();
    }
}
