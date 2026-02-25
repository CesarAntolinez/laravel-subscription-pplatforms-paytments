<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscountUsage extends Model
{
    use HasFactory;

    protected $fillable = [
        'discount_id', 'user_id', 'plan_id', 'subscription_id',
        'payment_id', 'amount_discounted', 'applied_at',
    ];

    protected $casts = [
        'applied_at' => 'datetime',
        'amount_discounted' => 'decimal:2',
    ];

    public function discount()
    {
        return $this->belongsTo(Discount::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}
