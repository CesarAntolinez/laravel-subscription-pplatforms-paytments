<?php

namespace Database\Factories;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        $subtotal  = $this->faker->randomFloat(2, 10, 500);
        $vatRate   = 0.16;
        $vatAmount = round($subtotal * $vatRate, 4);

        return [
            'idempotency_key'         => (string) Str::uuid(),
            'user_id'                 => \App\Models\User::factory(),
            'subscription_id'         => null,
            'provider'                => 'stripe',
            'provider_transaction_id' => null,
            'currency'                => 'USD',
            'subtotal'                => $subtotal,
            'vat_rate'                => $vatRate,
            'vat_amount'              => $vatAmount,
            'total'                   => round($subtotal + $vatAmount, 4),
            'vat_mode'                => 'excluded',
            'status'                  => 'pending',
            'description'             => 'Test payment',
            'metadata'                => [],
            'charged_at'              => null,
        ];
    }

    public function paid(): static
    {
        return $this->state([
            'status'                  => 'paid',
            'provider_transaction_id' => 'pi_' . Str::random(24),
            'charged_at'              => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(['status' => 'failed']);
    }
}
