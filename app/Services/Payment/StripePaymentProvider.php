<?php

namespace App\Services\Payment;

use App\Contracts\PaymentProviderInterface;
use Illuminate\Support\Facades\Log;

class StripePaymentProvider implements PaymentProviderInterface
{
    public function __construct(
        private readonly string $secretKey,
        private readonly string $webhookSecret
    ) {}

    public function getName(): string
    {
        return 'stripe';
    }

    public function charge(array $payload): array
    {
        Log::info('Stripe charge attempt', [
            'idempotency_key' => $payload['idempotency_key'],
            'amount'          => $payload['amount'],
            'currency'        => $payload['currency'],
        ]);

        // In a real implementation this would call the Stripe SDK.
        // Using a direct HTTP call here to avoid requiring the stripe/stripe-php package.
        $response = $this->request('POST', 'https://api.stripe.com/v1/payment_intents', [
            'amount'               => (int) round($payload['amount'] * 100),
            'currency'             => strtolower($payload['currency']),
            'description'          => $payload['description'] ?? '',
            'confirm'              => 'true',
            'automatic_payment_methods[enabled]' => 'true',
            'automatic_payment_methods[allow_redirects]' => 'never',
            'metadata'             => $payload['metadata'] ?? [],
        ], ['Idempotency-Key: ' . $payload['idempotency_key']]);

        return [
            'provider_transaction_id' => $response['id'] ?? '',
            'status'                  => $this->mapStatus($response['status'] ?? 'failed'),
            'raw'                     => $response,
        ];
    }

    public function parseWebhookEvent(string $rawPayload, string $signature): array
    {
        $this->validateSignature($rawPayload, $signature);

        $event = json_decode($rawPayload, true);

        $intentStatus = $event['data']['object']['status'] ?? 'unknown';
        $providerTxId  = $event['data']['object']['id'] ?? '';

        return [
            'type'                    => $event['type'] ?? 'unknown',
            'provider_transaction_id' => $providerTxId,
            'status'                  => $this->mapStatus($intentStatus),
            'raw'                     => $event,
        ];
    }

    // ------------------------------------------------------------------ helpers

    private function mapStatus(string $stripeStatus): string
    {
        return match ($stripeStatus) {
            'succeeded'              => 'paid',
            'requires_payment_method',
            'canceled'               => 'failed',
            default                  => $stripeStatus,
        };
    }

    private function validateSignature(string $rawPayload, string $signature): void
    {
        $parts     = [];
        foreach (explode(',', $signature) as $part) {
            [$k, $v]   = array_pad(explode('=', $part, 2), 2, '');
            $parts[$k] = $v;
        }
        $timestamp = $parts['t'] ?? 0;
        $expected  = hash_hmac('sha256', $timestamp . '.' . $rawPayload, $this->webhookSecret);

        if (!hash_equals($expected, $parts['v1'] ?? '')) {
            throw new \RuntimeException('Invalid Stripe webhook signature.');
        }
    }

    /**
     * Minimal cURL wrapper – only used in production; tests replace this class.
     *
     * @param  array<string, mixed>  $body
     * @param  list<string>          $extraHeaders
     * @return array<string, mixed>
     */
    private function request(string $method, string $url, array $body, array $extraHeaders = []): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD        => $this->secretKey . ':',
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($this->flattenMetadata($body)),
            CURLOPT_HTTPHEADER     => array_merge(['Content-Type: application/x-www-form-urlencoded'], $extraHeaders),
        ]);
        $raw    = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($raw ?: '{}', true) ?? [];

        if ($status >= 400) {
            throw new \RuntimeException('Stripe API error: ' . ($decoded['error']['message'] ?? $raw));
        }

        return $decoded;
    }

    /** @param array<string, mixed> $data */
    private function flattenMetadata(array $data): array
    {
        $flat = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $k => $v) {
                    $flat["{$key}[{$k}]"] = $v;
                }
            } else {
                $flat[$key] = $value;
            }
        }
        return $flat;
    }
}
