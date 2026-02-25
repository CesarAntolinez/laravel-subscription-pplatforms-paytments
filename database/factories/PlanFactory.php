<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PlanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'              => $this->faker->unique()->words(3, true),
            'description'       => $this->faker->sentence(),
            'status'            => 'active',
            'trial_days'        => 0,
            'auto_renew'        => true,
            'iva_percentage'    => 16,
            'iva_modality'      => 'excluded',
            'currency'          => 'MXN',
            'decimal_precision' => 2,
        ];
    }
}
