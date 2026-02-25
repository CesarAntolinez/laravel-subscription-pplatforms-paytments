<?php

namespace App\Providers;

use App\Contracts\PaymentProviderInterface;
use App\Services\Payment\PaymentProviderFactory;
use App\Services\Payment\PaymentService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(PaymentProviderInterface::class, function ($app) {
            return PaymentProviderFactory::make(config('payments'));
        });

        $this->app->singleton(PaymentService::class, function ($app) {
            return new PaymentService(
                $app->make(PaymentProviderInterface::class),
                config('payments.retry')
            );
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
