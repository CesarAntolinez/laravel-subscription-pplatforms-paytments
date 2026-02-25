<?php

namespace App\Http\Controllers;

use App\Models\Discount;
use App\Models\DiscountUsage;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * RF-ADM-001 — Operational dashboard with key metrics (HU-F1)
 * RF-ADM-006 — Monitor failed payments (HU-F2)
 */
class AdminDashboardController extends Controller
{
    /**
     * GET /admin/dashboard
     * Returns aggregate metrics for a date range.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'from' => 'nullable|date',
            'to'   => 'nullable|date|after_or_equal:from',
        ]);

        $from = $request->filled('from') ? $request->date('from') : now()->startOfMonth();
        $to   = $request->filled('to')   ? $request->date('to')->endOfDay() : now()->endOfDay();

        $activeSubscriptions = Subscription::where('status', 'active')
            ->whereBetween('starts_at', [$from, $to])
            ->count();

        $totalSubscriptions = Subscription::whereBetween('starts_at', [$from, $to])->count();

        $cancelledSubscriptions = Subscription::where('status', 'cancelled')
            ->whereBetween('cancelled_at', [$from, $to])
            ->count();

        $revenue = Payment::where('status', 'approved')
            ->whereBetween('charged_at', [$from, $to])
            ->selectRaw('SUM(total) as total_revenue, SUM(iva_amount) as total_iva, SUM(subtotal) as total_subtotal')
            ->first();

        $failedPayments = Payment::where('status', 'failed')
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $discountsApplied = DiscountUsage::whereBetween('applied_at', [$from, $to])->count();
        $totalDiscounted  = DiscountUsage::whereBetween('applied_at', [$from, $to])->sum('amount_discounted');

        $newUsers = User::whereBetween('created_at', [$from, $to])->count();

        return response()->json([
            'period' => [
                'from' => $from->toDateString(),
                'to'   => $to->toDateString(),
            ],
            'subscriptions' => [
                'active'    => $activeSubscriptions,
                'total'     => $totalSubscriptions,
                'cancelled' => $cancelledSubscriptions,
            ],
            'revenue' => [
                'subtotal' => (float) ($revenue->total_subtotal ?? 0),
                'iva'      => (float) ($revenue->total_iva ?? 0),
                'total'    => (float) ($revenue->total_revenue ?? 0),
            ],
            'payments' => [
                'failed' => $failedPayments,
            ],
            'discounts' => [
                'applied'          => $discountsApplied,
                'total_discounted' => (float) $totalDiscounted,
            ],
            'users' => [
                'new' => $newUsers,
            ],
        ]);
    }
}
