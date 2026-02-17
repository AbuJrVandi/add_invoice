<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;

class OwnerDashboardController extends Controller
{
    /**
     * Read-only owner analytics.
     *
     * Financial invariants enforced:
     * - Cash inflow from payments only
     * - Revenue from sales only
     * - Profit from sales snapshot only
     * - No revenue derived from invoices
     */
    public function index(): JsonResponse
    {
        $today = now();
        $startOfMonth = $today->copy()->startOfMonth();
        $endOfMonth = $today->copy()->endOfMonth();

        // Liquidity (payments table)
        $cashReceivedToday = (float) Payment::whereDate('paid_at', $today->toDateString())->sum('amount_paid');
        $cashReceivedThisMonth = (float) Payment::whereBetween('paid_at', [$startOfMonth, $endOfMonth])->sum('amount_paid');

        // Business performance (sales table)
        $completedSalesTotal = (float) Sale::sum('total_sale_price');
        $totalProfit = (float) Sale::sum('profit');
        $completedSalesCount = Sale::count();
        $averageProfitPerSale = $completedSalesCount > 0
            ? round($totalProfit / $completedSalesCount, 2)
            : 0.0;

        // Risk monitoring (invoices table)
        $outstandingBalance = (float) Invoice::whereIn('status', [Invoice::STATUS_PENDING, Invoice::STATUS_DUE])
            ->sum('balance_remaining');

        $overdueInvoicesCount = Invoice::query()
            ->whereIn('status', [Invoice::STATUS_PENDING, Invoice::STATUS_DUE])
            ->where('balance_remaining', '>', 0)
            ->whereDate('due_date', '<', $today->toDateString())
            ->count();

        $partiallyPaidInvoices = Invoice::query()
            ->where('amount_paid', '>', 0)
            ->where('balance_remaining', '>', 0)
            ->count();

        return response()->json([
            'as_of_date' => $today->toDateString(),
            'liquidity' => [
                'cash_received_today' => round($cashReceivedToday, 2),
                'cash_received_this_month' => round($cashReceivedThisMonth, 2),
            ],
            'business_performance' => [
                'completed_sales_total' => round($completedSalesTotal, 2),
                'total_profit' => round($totalProfit, 2),
                'average_profit_per_sale' => $averageProfitPerSale,
                'completed_sales_count' => $completedSalesCount,
            ],
            'risk_monitoring' => [
                'outstanding_balance' => round($outstandingBalance, 2),
                'overdue_invoices_count' => $overdueInvoicesCount,
                'partially_paid_invoices' => $partiallyPaidInvoices,
            ],
        ]);
    }
}
