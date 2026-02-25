<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Discount extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code', 'name', 'type', 'value', 'max_uses', 'used_count',
        'valid_from', 'valid_until', 'status',
        'restrict_plan_id', 'restrict_user_segment', 'restrict_signup_type',
    ];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'value' => 'decimal:2',
    ];

    public function usages()
    {
        return $this->hasMany(DiscountUsage::class);
    }

    public function restrictedPlan()
    {
        return $this->belongsTo(Plan::class, 'restrict_plan_id');
    }

    /**
     * Check if this discount is currently valid (business rules RN-DES-002).
     */
    public function isValid(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }
        $now = now();
        if ($this->valid_from && $now->lt($this->valid_from)) {
            return false;
        }
        if ($this->valid_until && $now->gt($this->valid_until)) {
            return false;
        }
        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) {
            return false;
        }
        return true;
    }

    /**
     * Calculate the discount amount for a given subtotal (RN-DES-003).
     */
    public function calculateDiscount(float $subtotal): float
    {
        if ($this->type === 'percentage') {
            return round($subtotal * ($this->value / 100), 2);
        }
        if ($this->type === 'fixed') {
            return min(round((float) $this->value, 2), $subtotal);
        }
        // free_trial: no monetary discount
        return 0.0;
    }
}
