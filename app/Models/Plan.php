<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'description', 'level', 'price', 'currency',
        'iva_porcentaje', 'modalidad_iva', 'billing_cycles',
        'trial_days', 'auto_renew', 'status',
    ];

    protected $casts = [
        'billing_cycles' => 'array',
        'auto_renew' => 'boolean',
        'price' => 'decimal:2',
        'iva_porcentaje' => 'decimal:2',
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}
