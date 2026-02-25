<?php

namespace Tests\Unit\Payment;

use App\Services\Payment\MercadoPagoPaymentProvider;
use App\Services\Payment\PaymentProviderFactory;
use App\Services\Payment\StripePaymentProvider;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class PaymentProviderFactoryTest extends TestCase
{
    public function test_factory_returns_stripe_provider(): void
    {
        $provider = PaymentProviderFactory::make([
            'default'   => 'stripe',
            'providers' => [
                'stripe' => ['secret_key' => 'sk_test', 'webhook_secret' => 'whsec'],
            ],
        ]);

        $this->assertInstanceOf(StripePaymentProvider::class, $provider);
        $this->assertEquals('stripe', $provider->getName());
    }

    public function test_factory_returns_mercadopago_provider(): void
    {
        $provider = PaymentProviderFactory::make([
            'default'   => 'mercadopago',
            'providers' => [
                'mercadopago' => ['access_token' => 'TEST-token', 'webhook_secret' => 'mp_secret'],
            ],
        ]);

        $this->assertInstanceOf(MercadoPagoPaymentProvider::class, $provider);
        $this->assertEquals('mercadopago', $provider->getName());
    }

    public function test_factory_throws_on_unsupported_driver(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported payment driver: paypal');

        PaymentProviderFactory::make(['default' => 'paypal', 'providers' => []]);
    }
}
