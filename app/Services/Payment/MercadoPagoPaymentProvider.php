<?php

namespace App\Services\Payment;

use App\Contracts\PaymentProviderInterface;
use Illuminate\Support\Facades\Log;

class MercadoPagoPaymentProvider implements PaymentProviderInterface
{
    public function __construct(
        private readonly string $accessToken,
        private readonly string $webhookSecret
    ) {}

    public function getName(): string
    {
        return 'mercadopago';
    }

    public function charge(array $payload): array
    {
        Log::info('MercadoPago charge attempt', [
            'idempotency_key' => $payload['idempotency_key'],
            'amount'          => $payload['amount'],
            'currency'        => $payload['currency'],
        ]);

        $body = [
            'transaction_amount' => (float) $payload['amount'],
            'description'        => $payload['description'] ?? '',
            'currency_id'        => strtoupper($payload['currency']),
            'external_reference' => $payload['idempotency_key'],
            'metadata'           => $payload['metadata'] ?? [],
        ];

        $response = $this->request(
            'POST',
            'https://api.mercadopago.com/v1/payments',
            $body,
            $payload['idempotency_key']
        );

        return [
            'provider_transaction_id' => (string) ($response['id'] ?? ''),
            'status'                  => $this->mapStatus($response['status'] ?? 'rejected'),
            'raw'                     => $response,
        ];
    }

    public function parseWebhookEvent(string $rawPayload, string $signature): array
    {
        $this->validateSignature($rawPayload, $signature);

        $event = json_decode($rawPayload, true);

        $providerTxId = (string) ($event['data']['id'] ?? '');
        $type         = $event['action'] ?? $event['type'] ?? 'unknown';
        $mpStatus     = $event['data']['status'] ?? 'unknown';

        return [
            'type'                    => $type,
            'provider_transaction_id' => $providerTxId,
            'status'                  => $this->mapStatus($mpStatus),
            'raw'                     => $event,
        ];
    }

    // ------------------------------------------------------------------ helpers

    private function mapStatus(string $mpStatus): string
    {
        return match ($mpStatus) {
            'approved'           => 'paid',
            'rejected', 'cancelled' => 'failed',
            'in_process', 'pending'  => 'pending',
            default               => $mpStatus,
        };
    }

    private function validateSignature(string $rawPayload, string $signature): void
    {
        $expected = hash_hmac('sha256', $rawPayload, $this->webhookSecret);
        if (!hash_equals($expected, $signature)) {
            throw new \RuntimeException('Invalid MercadoPago webhook signature.');
        }
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function request(string $method, string $url, array $body, string $idempotencyKey = ''): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($body),
            CURLOPT_HTTPHEADER     => array_filter([
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->accessToken,
                $idempotencyKey ? 'X-Idempotency-Key: ' . $idempotencyKey : null,
            ]),
        ]);
        $raw    = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($raw ?: '{}', true) ?? [];

        if ($status >= 400) {
            throw new \RuntimeException('MercadoPago API error: ' . ($decoded['message'] ?? $raw));
        }

        return $decoded;
    }
}
