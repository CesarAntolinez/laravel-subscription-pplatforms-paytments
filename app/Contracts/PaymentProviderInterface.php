<?php

namespace App\Contracts;

use App\Models\Transaction;

interface PaymentProviderInterface
{
    /**
     * Charge the given amount for a subscription.
     *
     * @param  array{
     *     amount: float,
     *     currency: string,
     *     description: string,
     *     idempotency_key: string,
     *     metadata: array<string, mixed>,
     * } $payload
     * @return array{
     *     provider_transaction_id: string,
     *     status: string,
     *     raw: mixed,
     * }
     */
    public function charge(array $payload): array;

    /**
     * Validate a webhook payload and return the parsed event.
     *
     * @param  string $rawPayload
     * @param  string $signature
     * @return array{type: string, provider_transaction_id: string, status: string, raw: mixed}
     */
    public function parseWebhookEvent(string $rawPayload, string $signature): array;

    /**
     * Return the provider name identifier.
     */
    public function getName(): string;
}
