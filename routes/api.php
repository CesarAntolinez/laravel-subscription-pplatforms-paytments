<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/*
|--------------------------------------------------------------------------
| Payment Webhooks
|--------------------------------------------------------------------------
| These endpoints receive events from payment providers (Stripe,
| MercadoPago). CSRF verification is excluded for these routes via
| the VerifyCsrfToken middleware exception list.
*/
Route::post('/webhooks/payments', [\App\Http\Controllers\Api\WebhookController::class, 'handle'])
    ->name('webhooks.payments');
