<?php

namespace Tests\Feature\Payment;

use App\Contracts\PaymentProviderInterface;
use App\Models\PaymentEvent;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookReconciliationTest extends TestCase
{
    use RefreshDatabase;

    private PaymentProviderInterface $mockProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockProvider = $this->createMock(PaymentProviderInterface::class);
        $this->mockProvider->method('getName')->willReturn('stripe');

        // Swap the real provider with the mock in the container
        $this->app->instance(PaymentProviderInterface::class, $this->mockProvider);
    }

    // ------------------------------------------------------------------ helpers

    private function makeStripeSignature(string $payload, string $secret): string
    {
        $timestamp = time();
        $sig       = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
        return "t={$timestamp},v1={$sig}";
    }

    // ------------------------------------------------------------------ tests

    public function test_webhook_updates_transaction_status_to_paid(): void
    {
        $user = User::factory()->create();

        $tx = Transaction::factory()->create([
            'user_id'                 => $user->id,
            'provider'                => 'stripe',
            'provider_transaction_id' => 'pi_evt_001',
            'status'                  => 'pending',
        ]);

        $rawPayload = json_encode([
            'id'   => 'evt_test_001',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id'     => 'pi_evt_001',
                    'status' => 'succeeded',
                ],
            ],
        ]);

        $this->mockProvider
            ->method('parseWebhookEvent')
            ->willReturn([
                'type'                    => 'payment_intent.succeeded',
                'provider_transaction_id' => 'pi_evt_001',
                'status'                  => 'paid',
                'raw'                     => json_decode($rawPayload, true),
            ]);

        $response = $this->postJson('/api/webhooks/payments', json_decode($rawPayload, true), [
            'Stripe-Signature' => 'dummy',
        ]);

        $response->assertStatus(200);

        $tx->refresh();
        $this->assertEquals('paid', $tx->status);
        $this->assertNotNull($tx->charged_at);
    }

    public function test_duplicate_webhook_event_is_ignored(): void
    {
        $user = User::factory()->create();

        $tx = Transaction::factory()->create([
            'user_id'                 => $user->id,
            'provider'                => 'stripe',
            'provider_transaction_id' => 'pi_dup_001',
            'status'                  => 'pending',
        ]);

        // Pre-insert a processed event to simulate already-processed state
        PaymentEvent::create([
            'provider'                => 'stripe',
            'event_id'                => 'evt_dup_001',
            'event_type'              => 'payment_intent.succeeded',
            'provider_transaction_id' => 'pi_dup_001',
            'payload'                 => [],
            'processed'               => true,
        ]);

        $rawPayload = json_encode([
            'id'   => 'evt_dup_001',
            'type' => 'payment_intent.succeeded',
            'data' => ['object' => ['id' => 'pi_dup_001', 'status' => 'succeeded']],
        ]);

        $this->mockProvider
            ->method('parseWebhookEvent')
            ->willReturn([
                'type'                    => 'payment_intent.succeeded',
                'provider_transaction_id' => 'pi_dup_001',
                'status'                  => 'paid',
                'raw'                     => ['id' => 'evt_dup_001'],
            ]);

        $response = $this->postJson('/api/webhooks/payments', json_decode($rawPayload, true), [
            'Stripe-Signature' => 'dummy',
        ]);

        $response->assertStatus(200);
        $response->assertSee('Already processed');

        // Status must NOT have changed
        $this->assertEquals('pending', $tx->fresh()->status);
    }

    public function test_invalid_webhook_signature_returns_400(): void
    {
        $this->mockProvider
            ->method('parseWebhookEvent')
            ->willThrowException(new \RuntimeException('Invalid Stripe webhook signature.'));

        $response = $this->postJson('/api/webhooks/payments', ['id' => 'evt_bad'], [
            'Stripe-Signature' => 'bad_sig',
        ]);

        $response->assertStatus(400);
    }

    public function test_webhook_does_not_double_update_already_paid_transaction(): void
    {
        $user = User::factory()->create();

        $tx = Transaction::factory()->paid()->create([
            'user_id'                 => $user->id,
            'provider'                => 'stripe',
            'provider_transaction_id' => 'pi_already_paid',
        ]);

        $chargedAt = $tx->charged_at;

        $rawPayload = json_encode([
            'id'   => 'evt_repay_001',
            'type' => 'payment_intent.succeeded',
            'data' => ['object' => ['id' => 'pi_already_paid', 'status' => 'succeeded']],
        ]);

        $this->mockProvider
            ->method('parseWebhookEvent')
            ->willReturn([
                'type'                    => 'payment_intent.succeeded',
                'provider_transaction_id' => 'pi_already_paid',
                'status'                  => 'paid',
                'raw'                     => ['id' => 'evt_repay_001'],
            ]);

        $response = $this->postJson('/api/webhooks/payments', json_decode($rawPayload, true), [
            'Stripe-Signature' => 'dummy',
        ]);

        $response->assertStatus(200);

        // charged_at should remain the same (no double update)
        $this->assertEquals(
            $chargedAt->toDateTimeString(),
            $tx->fresh()->charged_at->toDateTimeString()
        );
    }
}
