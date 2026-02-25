<?php

namespace App\Http\Controllers;

use App\Models\DiscountUsage;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * RF-DES-005, RNF-DES-002 — Discount usage report (HU-E3)
 * RF-ADM-006              — Failed payments report (HU-F2)
 */
class ReportController extends Controller
{
    /**
     * GET /reports/discount-usage
     * Discount usage history with filters by period and discount code/campaign.
     */
    public function discountUsage(Request $request): JsonResponse
    {
        $request->validate([
            'from'        => 'nullable|date',
            'to'          => 'nullable|date|after_or_equal:from',
            'discount_id' => 'nullable|exists:discounts,id',
            'code'        => 'nullable|string',
        ]);

        $from = $request->filled('from') ? $request->date('from') : now()->startOfMonth();
        $to   = $request->filled('to')   ? $request->date('to')->endOfDay() : now()->endOfDay();

        $query = DiscountUsage::with([
            'discount:id,code,name,type,value',
            'user:id,name,email',
            'plan:id,name',
            'subscription:id,status',
        ])->whereBetween('applied_at', [$from, $to]);

        if ($request->filled('discount_id')) {
            $query->where('discount_id', $request->discount_id);
        }

        if ($request->filled('code')) {
            $query->whereHas('discount', fn ($q) => $q->where('code', $request->code));
        }

        $usages = $query->orderByDesc('applied_at')->paginate(50);

        $summaryQuery = DiscountUsage::whereBetween('applied_at', [$from, $to]);
        if ($request->filled('discount_id')) {
            $summaryQuery->where('discount_id', $request->discount_id);
        }
        if ($request->filled('code')) {
            $summaryQuery->whereHas('discount', fn ($q) => $q->where('code', $request->code));
        }

        $summary = [
            'total_usages'     => $summaryQuery->count(),
            'total_discounted' => (float) $summaryQuery->sum('amount_discounted'),
        ];

        return response()->json([
            'summary' => $summary,
            'data'    => $usages,
        ]);
    }

    /**
     * GET /reports/failed-payments
     * Failed payments report for operational support.
     */
    public function failedPayments(Request $request): JsonResponse
    {
        $request->validate([
            'from' => 'nullable|date',
            'to'   => 'nullable|date|after_or_equal:from',
        ]);

        $from = $request->filled('from') ? $request->date('from') : now()->startOfMonth();
        $to   = $request->filled('to')   ? $request->date('to')->endOfDay() : now()->endOfDay();

        $payments = Payment::with(['subscription:id,status,plan_id'])
            ->where('status', 'failed')
            ->whereBetween('created_at', [$from, $to])
            ->orderByDesc('created_at')
            ->paginate(50);

        $summary = [
            'total_failed' => Payment::where('status', 'failed')
                ->whereBetween('created_at', [$from, $to])->count(),
            'total_amount' => Payment::where('status', 'failed')
                ->whereBetween('created_at', [$from, $to])->sum('total'),
        ];

        return response()->json([
            'summary' => $summary,
            'data'    => $payments,
        ]);
    }
}
