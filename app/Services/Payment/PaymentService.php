<?php

namespace App\Services\Payment;

use App\Contracts\PaymentProviderInterface;
use App\Models\PaymentRetry;
use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentService
{
    public function __construct(
        private readonly PaymentProviderInterface $provider,
        private readonly array $retryConfig
    ) {}

    /**
     * Charge an initial subscription payment.
     *
     * @param  array{
     *     user_id: int,
     *     subscription_id?: int,
     *     amount: float,
     *     currency: string,
     *     vat_rate: float,
     *     vat_mode: string,
     *     description?: string,
     *     metadata?: array<string, mixed>,
     * } $data
     */
    public function chargeInitial(array $data): Transaction
    {
        return $this->charge($data, 'initial');
    }

    /**
     * Charge a subscription renewal.
     *
     * @param  array{
     *     user_id: int,
     *     subscription_id?: int,
     *     amount: float,
     *     currency: string,
     *     vat_rate: float,
     *     vat_mode: string,
     *     description?: string,
     *     metadata?: array<string, mixed>,
     * } $data
     */
    public function chargeRenewal(array $data): Transaction
    {
        return $this->charge($data, 'renewal');
    }

    /**
     * Execute a retry attempt for a previously failed transaction.
     */
    public function retryTransaction(Transaction $transaction): Transaction
    {
        if ($transaction->isPaid()) {
            Log::info('PaymentService: transaction already paid, skipping retry.', [
                'transaction_id' => $transaction->id,
            ]);
            return $transaction;
        }

        $attemptNumber = $transaction->retries()->count() + 1;
        $maxAttempts   = (int) ($this->retryConfig['max_attempts'] ?? 3);

        if ($attemptNumber > $maxAttempts) {
            Log::warning('PaymentService: max retry attempts reached.', [
                'transaction_id' => $transaction->id,
                'max_attempts'   => $maxAttempts,
            ]);
            return $transaction;
        }

        $retry = PaymentRetry::create([
            'transaction_id' => $transaction->id,
            'attempt_number' => $attemptNumber,
            'status'         => 'pending',
            'scheduled_at'   => now(),
        ]);

        try {
            $result = $this->provider->charge([
                'amount'          => $transaction->total,
                'currency'        => $transaction->currency,
                'description'     => $transaction->description ?? '',
                'idempotency_key' => $transaction->idempotency_key . '_retry' . $attemptNumber,
                'metadata'        => $transaction->metadata ?? [],
            ]);

            $retry->update([
                'status'      => 'succeeded',
                'executed_at' => now(),
            ]);

            $transaction->update([
                'status'                  => $result['status'],
                'provider_transaction_id' => $result['provider_transaction_id'],
                'charged_at'              => $result['status'] === 'paid' ? now() : null,
            ]);

            Log::info('PaymentService: retry succeeded.', [
                'transaction_id' => $transaction->id,
                'attempt_number' => $attemptNumber,
            ]);
        } catch (\Throwable $e) {
            $retry->update([
                'status'         => 'failed',
                'failure_reason' => $e->getMessage(),
                'executed_at'    => now(),
            ]);

            Log::error('PaymentService: retry failed.', [
                'transaction_id' => $transaction->id,
                'attempt_number' => $attemptNumber,
                'error'          => $e->getMessage(),
            ]);
        }

        return $transaction->fresh();
    }

    // ------------------------------------------------------------------ private

    /**
     * @param  array<string, mixed>  $data
     */
    private function charge(array $data, string $type): Transaction
    {
        [$subtotal, $vatAmount, $total] = $this->calculateVat(
            (float) $data['amount'],
            (float) $data['vat_rate'],
            $data['vat_mode'] ?? 'excluded'
        );

        $idempotencyKey = $data['idempotency_key']
            ?? Str::uuid()->toString();

        $transaction = DB::transaction(function () use ($data, $type, $subtotal, $vatAmount, $total, $idempotencyKey): Transaction {
            return Transaction::create([
                'idempotency_key'         => $idempotencyKey,
                'user_id'                 => $data['user_id'],
                'subscription_id'         => $data['subscription_id'] ?? null,
                'provider'                => $this->provider->getName(),
                'currency'                => strtoupper($data['currency']),
                'subtotal'                => $subtotal,
                'vat_rate'                => $data['vat_rate'],
                'vat_amount'              => $vatAmount,
                'total'                   => $total,
                'vat_mode'                => $data['vat_mode'] ?? 'excluded',
                'status'                  => 'pending',
                'description'             => $data['description'] ?? ucfirst($type) . ' payment',
                'metadata'                => $data['metadata'] ?? [],
            ]);
        });

        try {
            $result = $this->provider->charge([
                'amount'          => $total,
                'currency'        => $transaction->currency,
                'description'     => $transaction->description,
                'idempotency_key' => $idempotencyKey,
                'metadata'        => array_merge($data['metadata'] ?? [], [
                    'transaction_id'  => $transaction->id,
                    'type'            => $type,
                ]),
            ]);

            $transaction->update([
                'status'                  => $result['status'],
                'provider_transaction_id' => $result['provider_transaction_id'],
                'charged_at'              => $result['status'] === 'paid' ? now() : null,
            ]);

            Log::info('PaymentService: charge completed.', [
                'transaction_id' => $transaction->id,
                'type'           => $type,
                'status'         => $result['status'],
            ]);
        } catch (\Throwable $e) {
            $transaction->update(['status' => 'failed']);
            Log::error('PaymentService: charge failed.', [
                'transaction_id' => $transaction->id,
                'type'           => $type,
                'error'          => $e->getMessage(),
            ]);
            $this->scheduleRetries($transaction);
        }

        return $transaction->fresh();
    }

    /**
     * Calculate subtotal, VAT amount and total.
     *
     * @return array{float, float, float}  [subtotal, vat_amount, total]
     */
    private function calculateVat(float $amount, float $vatRate, string $vatMode): array
    {
        if ($vatMode === 'included') {
            // Amount already contains VAT
            $subtotal  = round($amount / (1 + $vatRate), 4);
            $vatAmount = round($amount - $subtotal, 4);
            $total     = round($amount, 4);
        } else {
            // VAT is added on top
            $subtotal  = round($amount, 4);
            $vatAmount = round($amount * $vatRate, 4);
            $total     = round($subtotal + $vatAmount, 4);
        }

        return [$subtotal, $vatAmount, $total];
    }

    /**
     * Schedule future retry records based on the retry config.
     */
    private function scheduleRetries(Transaction $transaction): void
    {
        $maxAttempts = (int) ($this->retryConfig['max_attempts'] ?? 3);
        $delays      = $this->retryConfig['delays_minutes'] ?? [1440, 2880];

        for ($i = 1; $i < $maxAttempts; $i++) {
            $delayMinutes = $delays[$i - 1] ?? end($delays);
            PaymentRetry::create([
                'transaction_id' => $transaction->id,
                'attempt_number' => $i + 1,
                'status'         => 'pending',
                'scheduled_at'   => now()->addMinutes((int) $delayMinutes),
            ]);
        }
    }
}
