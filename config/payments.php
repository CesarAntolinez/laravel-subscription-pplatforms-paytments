<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Payment Driver
    |--------------------------------------------------------------------------
    | Supported: "stripe", "mercadopago"
    */
    'default' => env('PAYMENT_DRIVER', 'stripe'),

    /*
    |--------------------------------------------------------------------------
    | Provider credentials
    |--------------------------------------------------------------------------
    */
    'providers' => [

        'stripe' => [
            'secret_key'     => env('STRIPE_SECRET_KEY', ''),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET', ''),
        ],

        'mercadopago' => [
            'access_token'   => env('MERCADOPAGO_ACCESS_TOKEN', ''),
            'webhook_secret' => env('MERCADOPAGO_WEBHOOK_SECRET', ''),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Retry policy
    |--------------------------------------------------------------------------
    | max_attempts : total tries (1 = no retry)
    | delays_minutes: minutes to wait before each subsequent attempt (0-indexed)
    |                 Pad with the last value if fewer entries than max_attempts-1
    */
    'retry' => [
        'max_attempts'   => (int) env('PAYMENT_RETRY_MAX_ATTEMPTS', 3),
        'delays_minutes' => [1440, 2880],   // 24 h, 48 h
    ],

    /*
    |--------------------------------------------------------------------------
    | VAT default (used when plan does not override)
    |--------------------------------------------------------------------------
    */
    'vat_rate_default' => (float) env('PAYMENT_VAT_RATE_DEFAULT', 0.16),

];
