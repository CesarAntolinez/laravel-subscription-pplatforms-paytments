<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\DiscountController;
use App\Http\Controllers\ReportController;
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

// -------------------------------------------------------------------
// Sprint 4 — Discounts, Checkout, Admin Dashboard, Reports
// -------------------------------------------------------------------

// HU-E1: Discount CRUD + validation
Route::apiResource('discounts', DiscountController::class);
Route::post('discounts/validate', [DiscountController::class, 'validateCode']);

// HU-E2: Checkout (preview + confirm) — requires authenticated user
Route::middleware('auth:sanctum')->group(function () {
    Route::post('checkout/preview', [CheckoutController::class, 'preview']);
    Route::post('checkout/confirm', [CheckoutController::class, 'confirm']);
});

// HU-F1, HU-F2: Admin dashboard + reports
Route::prefix('admin')->group(function () {
    Route::get('dashboard', [AdminDashboardController::class, 'index']);
});

// HU-E3, HU-F2: Operational reports
Route::prefix('reports')->group(function () {
    Route::get('discount-usage', [ReportController::class, 'discountUsage']);
    Route::get('failed-payments', [ReportController::class, 'failedPayments']);
});
