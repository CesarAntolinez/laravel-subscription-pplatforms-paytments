<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class DiscountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code'        => strtoupper($this->faker->unique()->lexify('????##')),
            'name'        => $this->faker->words(3, true),
            'type'        => $this->faker->randomElement(['percentage', 'fixed', 'free_trial']),
            'value'       => $this->faker->randomFloat(2, 5, 50),
            'max_uses'    => null,
            'used_count'  => 0,
            'valid_from'  => null,
            'valid_until' => null,
            'status'      => 'active',
        ];
    }
}
