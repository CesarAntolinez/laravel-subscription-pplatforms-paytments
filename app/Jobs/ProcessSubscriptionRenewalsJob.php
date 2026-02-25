<?php

namespace App\Jobs;

use App\Models\Subscription;
use App\Services\SubscriptionService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessSubscriptionRenewalsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(SubscriptionService $subscriptionService): void
    {
        $due = Subscription::query()
            ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIAL])
            ->where('next_billing_at', '<=', Carbon::now())
            ->with('plan')
            ->get();

        foreach ($due as $subscription) {
            try {
                $subscriptionService->renew($subscription);
                Log::info("Suscripción #{$subscription->id} renovada correctamente.");
            } catch (\Throwable $e) {
                Log::error("Error al renovar suscripción #{$subscription->id}: {$e->getMessage()}");
            }
        }
    }
}
