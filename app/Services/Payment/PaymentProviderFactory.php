<?php

namespace App\Services\Payment;

use App\Contracts\PaymentProviderInterface;
use InvalidArgumentException;

class PaymentProviderFactory
{
    /**
     * Build a PaymentProviderInterface from configuration.
     *
     * @param  array<string, mixed>  $config  Full payments config array.
     */
    public static function make(array $config): PaymentProviderInterface
    {
        $driver = $config['default'] ?? 'stripe';

        return match ($driver) {
            'stripe' => new StripePaymentProvider(
                secretKey:     $config['providers']['stripe']['secret_key'] ?? '',
                webhookSecret: $config['providers']['stripe']['webhook_secret'] ?? ''
            ),
            'mercadopago' => new MercadoPagoPaymentProvider(
                accessToken:   $config['providers']['mercadopago']['access_token'] ?? '',
                webhookSecret: $config['providers']['mercadopago']['webhook_secret'] ?? ''
            ),
            default => throw new InvalidArgumentException("Unsupported payment driver: {$driver}"),
        };
    }
}
