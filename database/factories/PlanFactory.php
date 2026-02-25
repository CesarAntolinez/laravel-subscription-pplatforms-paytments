<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PlanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'           => $this->faker->unique()->words(3, true),
            'description'    => $this->faker->sentence(),
            'level'          => $this->faker->randomElement(['basic', 'standard', 'premium']),
            'price'          => $this->faker->randomFloat(2, 5, 500),
            'currency'       => 'MXN',
            'iva_porcentaje' => 16,
            'modalidad_iva'  => 'excluded',
            'billing_cycles' => ['monthly'],
            'trial_days'     => 0,
            'auto_renew'     => true,
            'status'         => 'active',
        ];
    }
}
