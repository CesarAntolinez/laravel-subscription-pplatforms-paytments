<?php

namespace Database\Factories;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'         => User::factory(),
            'plan_id'         => Plan::factory(),
            'status'          => 'active',
            'billing_cycle'   => 'monthly',
            'starts_at'       => now(),
            'next_billing_at' => now()->addMonth(),
        ];
    }
}
