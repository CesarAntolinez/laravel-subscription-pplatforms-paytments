<?php

namespace Tests\Feature\Payment;

use App\Contracts\PaymentProviderInterface;
use App\Models\PaymentEvent;
use App\Models\PaymentRetry;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Payment\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Integration tests for the payment module (Sprint 3).
 *
 * All tests use a mocked PaymentProviderInterface so no real API calls are made.
 */
class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    // ------------------------------------------------------------------ setup

    private PaymentProviderInterface $mockProvider;
    private PaymentService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockProvider = $this->createMock(PaymentProviderInterface::class);
        $this->mockProvider->method('getName')->willReturn('stripe');

        $this->service = new PaymentService(
            $this->mockProvider,
            ['max_attempts' => 3, 'delays_minutes' => [1, 2]]
        );

        $this->user = User::factory()->create();
    }

    // ------------------------------------------------------------------ chargeInitial

    public function test_charge_initial_creates_transaction_with_vat_breakdown(): void
    {
        $this->mockProvider
            ->expects($this->once())
            ->method('charge')
            ->willReturn([
                'provider_transaction_id' => 'pi_test_001',
                'status'                  => 'paid',
                'raw'                     => [],
            ]);

        $tx = $this->service->chargeInitial([
            'user_id'  => $this->user->id,
            'amount'   => 100.0,
            'currency' => 'USD',
            'vat_rate' => 0.16,
            'vat_mode' => 'excluded',
        ]);

        $this->assertInstanceOf(Transaction::class, $tx);
        $this->assertEquals('paid', $tx->status);
        $this->assertEquals('100.0000', $tx->subtotal);
        $this->assertEquals('0.1600', $tx->vat_rate);
        $this->assertEquals('16.0000', $tx->vat_amount);
        $this->assertEquals('116.0000', $tx->total);
        $this->assertEquals('pi_test_001', $tx->provider_transaction_id);
        $this->assertNotNull($tx->charged_at);
    }

    public function test_charge_initial_with_vat_included_mode(): void
    {
        $this->mockProvider
            ->method('charge')
            ->willReturn(['provider_transaction_id' => 'pi_test_002', 'status' => 'paid', 'raw' => []]);

        // Amount of 116 already includes 16% VAT
        $tx = $this->service->chargeInitial([
            'user_id'  => $this->user->id,
            'amount'   => 116.0,
            'currency' => 'USD',
            'vat_rate' => 0.16,
            'vat_mode' => 'included',
        ]);

        $this->assertEquals('100.0000', $tx->subtotal);
        $this->assertEquals('16.0000', $tx->vat_amount);
        $this->assertEquals('116.0000', $tx->total);
    }

    // ------------------------------------------------------------------ chargeRenewal

    public function test_charge_renewal_records_transaction(): void
    {
        $this->mockProvider
            ->method('charge')
            ->willReturn(['provider_transaction_id' => 'pi_renew_001', 'status' => 'paid', 'raw' => []]);

        $tx = $this->service->chargeRenewal([
            'user_id'         => $this->user->id,
            'subscription_id' => 42,
            'amount'          => 50.0,
            'currency'        => 'MXN',
            'vat_rate'        => 0.16,
            'vat_mode'        => 'excluded',
            'description'     => 'Renewal payment',
        ]);

        $this->assertEquals('paid', $tx->status);
        $this->assertEquals(42, $tx->subscription_id);
        $this->assertEquals('MXN', $tx->currency);
        $this->assertDatabaseHas('transactions', [
            'id'     => $tx->id,
            'status' => 'paid',
        ]);
    }

    // ------------------------------------------------------------------ failed payment & retries

    public function test_failed_charge_schedules_retries(): void
    {
        $this->mockProvider
            ->method('charge')
            ->willThrowException(new \RuntimeException('Card declined'));

        $tx = $this->service->chargeInitial([
            'user_id'  => $this->user->id,
            'amount'   => 100.0,
            'currency' => 'USD',
            'vat_rate' => 0.16,
            'vat_mode' => 'excluded',
        ]);

        $this->assertEquals('failed', $tx->status);

        // max_attempts=3, so 2 retry records should be scheduled (attempts 2 & 3)
        $this->assertDatabaseCount('payment_retries', 2);
        $retries = PaymentRetry::where('transaction_id', $tx->id)->get();
        $this->assertEquals(2, $retries->first()->attempt_number);
        $this->assertEquals(3, $retries->last()->attempt_number);
        $this->assertEquals('pending', $retries->first()->status);
    }

    public function test_retry_succeeds_and_updates_transaction(): void
    {
        $this->mockProvider
            ->method('charge')
            ->willReturnOnConsecutiveCalls(
                $this->throwException(new \RuntimeException('First failure')),
                ['provider_transaction_id' => 'pi_retry_ok', 'status' => 'paid', 'raw' => []]
            );

        // First charge fails
        $tx = $this->service->chargeInitial([
            'user_id'  => $this->user->id,
            'amount'   => 100.0,
            'currency' => 'USD',
            'vat_rate' => 0.16,
            'vat_mode' => 'excluded',
        ]);
        $this->assertEquals('failed', $tx->status);

        // Retry the transaction
        $tx = $this->service->retryTransaction($tx);

        $this->assertEquals('paid', $tx->status);
        $this->assertEquals('pi_retry_ok', $tx->provider_transaction_id);
    }

    public function test_retry_on_already_paid_transaction_is_skipped(): void
    {
        $this->mockProvider->expects($this->once())->method('charge')
            ->willReturn(['provider_transaction_id' => 'pi_paid', 'status' => 'paid', 'raw' => []]);

        $tx = $this->service->chargeInitial([
            'user_id'  => $this->user->id,
            'amount'   => 100.0,
            'currency' => 'USD',
            'vat_rate' => 0.16,
            'vat_mode' => 'excluded',
        ]);

        // Retry should be a no-op
        $txAfterRetry = $this->service->retryTransaction($tx);
        $this->assertEquals('paid', $txAfterRetry->status);
    }

    public function test_max_retry_attempts_are_not_exceeded(): void
    {
        $this->mockProvider->method('charge')
            ->willThrowException(new \RuntimeException('Always fails'));

        $tx = $this->service->chargeInitial([
            'user_id'  => $this->user->id,
            'amount'   => 100.0,
            'currency' => 'USD',
            'vat_rate' => 0.16,
            'vat_mode' => 'excluded',
        ]);

        // Simulate retries beyond max_attempts (3)
        $this->service->retryTransaction($tx->fresh());
        $this->service->retryTransaction($tx->fresh());
        $result = $this->service->retryTransaction($tx->fresh()); // 4th attempt, must be skipped

        // Still failed, no more retries were executed
        $this->assertEquals('failed', $result->status);
    }

    // ------------------------------------------------------------------ idempotency

    public function test_idempotency_key_is_unique_per_transaction(): void
    {
        $this->mockProvider->method('charge')
            ->willReturn(['provider_transaction_id' => 'pi_idem', 'status' => 'paid', 'raw' => []]);

        $tx1 = $this->service->chargeInitial([
            'user_id'         => $this->user->id,
            'amount'          => 100.0,
            'currency'        => 'USD',
            'vat_rate'        => 0.16,
            'vat_mode'        => 'excluded',
            'idempotency_key' => 'key-abc-123',
        ]);

        $this->assertEquals('key-abc-123', $tx1->idempotency_key);

        // Trying to create another transaction with the same key must fail (unique constraint)
        $this->expectException(\Throwable::class);
        $this->service->chargeInitial([
            'user_id'         => $this->user->id,
            'amount'          => 100.0,
            'currency'        => 'USD',
            'vat_rate'        => 0.16,
            'vat_mode'        => 'excluded',
            'idempotency_key' => 'key-abc-123',
        ]);
    }
}
