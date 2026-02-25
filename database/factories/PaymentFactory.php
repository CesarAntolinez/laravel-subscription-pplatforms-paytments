<?php

namespace Database\Factories;

use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PaymentFactory extends Factory
{
    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 50, 500);
        $ivaRate  = 0.16;
        $iva      = round($subtotal * $ivaRate, 2);

        return [
            'subscription_id'        => Subscription::factory(),
            'provider'               => 'stripe',
            'currency'               => 'MXN',
            'subtotal'               => $subtotal,
            'iva_percentage_applied' => 16,
            'iva_amount'             => $iva,
            'total'                  => $subtotal + $iva,
            'iva_modality'           => 'excluded',
            'base_imponible'         => $subtotal,
            'idempotency_key'        => (string) Str::uuid(),
            'billed_at'              => now(),
            'status'                 => 'paid',
        ];
    }
}
