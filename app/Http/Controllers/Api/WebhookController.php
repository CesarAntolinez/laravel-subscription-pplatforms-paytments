<?php

namespace App\Http\Controllers\Api;

use App\Contracts\PaymentProviderInterface;
use App\Http\Controllers\Controller;
use App\Models\PaymentEvent;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        private readonly PaymentProviderInterface $provider
    ) {}

    /**
     * Handle incoming webhook from the configured payment provider.
     */
    public function handle(Request $request): Response
    {
        $rawPayload = $request->getContent();
        $signature  = $request->header('Stripe-Signature')
            ?? $request->header('X-Signature')
            ?? '';

        try {
            $event = $this->provider->parseWebhookEvent($rawPayload, $signature);
        } catch (\RuntimeException $e) {
            Log::warning('Webhook signature validation failed.', ['error' => $e->getMessage()]);
            return response('Invalid signature', 400);
        }

        $providerName = $this->provider->getName();
        $eventId      = $event['raw']['id'] ?? $event['raw']['action'] ?? md5($rawPayload);

        // ── Idempotency guard: skip duplicate events ─────────────────────────
        if (PaymentEvent::alreadyProcessed($providerName, $eventId)) {
            Log::info('Webhook: duplicate event ignored.', [
                'provider' => $providerName,
                'event_id' => $eventId,
            ]);
            return response('Already processed', 200);
        }

        DB::transaction(function () use ($providerName, $eventId, $event, $rawPayload) {
            // Persist the raw event first (unique constraint prevents duplicates)
            PaymentEvent::firstOrCreate(
                ['provider' => $providerName, 'event_id' => $eventId],
                [
                    'event_type'              => $event['type'],
                    'provider_transaction_id' => $event['provider_transaction_id'],
                    'payload'                 => json_decode($rawPayload, true),
                    'processed'               => false,
                ]
            );

            $this->processEvent($providerName, $eventId, $event);
        });

        return response('OK', 200);
    }

    // ------------------------------------------------------------------ private

    private function processEvent(string $providerName, string $eventId, array $event): void
    {
        $providerTxId = $event['provider_transaction_id'] ?? null;
        $status       = $event['status'] ?? null;

        if ($providerTxId && $status) {
            $transaction = Transaction::where('provider', $providerName)
                ->where('provider_transaction_id', $providerTxId)
                ->first();

            if ($transaction && !$transaction->isPaid()) {
                $transaction->update([
                    'status'     => $status,
                    'charged_at' => $status === 'paid' ? now() : $transaction->charged_at,
                ]);

                Log::info('Webhook: transaction status updated.', [
                    'transaction_id' => $transaction->id,
                    'status'         => $status,
                    'event_type'     => $event['type'],
                ]);
            }
        }

        PaymentEvent::where('provider', $providerName)
            ->where('event_id', $eventId)
            ->update(['processed' => true]);
    }
}
