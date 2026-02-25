<?php

namespace Database\Factories;

use App\Models\Discount;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DiscountUsageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'discount_id'       => Discount::factory(),
            'user_id'           => User::factory(),
            'plan_id'           => Plan::factory(),
            'subscription_id'   => Subscription::factory(),
            'payment_id'        => null,
            'amount_discounted' => $this->faker->randomFloat(2, 1, 100),
            'applied_at'        => now(),
        ];
    }
}
