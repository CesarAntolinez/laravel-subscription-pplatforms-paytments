<?php

use App\Http\Controllers\Admin\PlanController;
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

Route::post('/webhooks/payments', [\App\Http\Controllers\Api\WebhookController::class, 'handle'])
    ->name('webhooks.payments');

// Admin routes — require authentication + admin role
Route::prefix('admin')
    ->middleware(['auth:sanctum', 'admin'])
    ->name('admin.')
    ->group(function () {
        // Plans CRUD
        Route::apiResource('plans', PlanController::class);
        Route::patch('plans/{plan}/toggle-status', [PlanController::class, 'toggleStatus'])
            ->name('plans.toggle-status');
    });
