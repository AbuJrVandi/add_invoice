<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $totalInvoices = Invoice::count();
        $cashCollected = (float) Payment::sum('amount_paid');
        $earnedRevenue = (float) Sale::sum('total_sale_price');
        $totalProfit   = (float) Sale::sum('profit');

        // Volume and collections statistics
        $totalSales          = Sale::count();
        $outstandingInvoices = Invoice::whereIn('status', [Invoice::STATUS_PENDING, Invoice::STATUS_DUE])->count();
        $outstandingAmount   = (float) Invoice::whereIn('status', [Invoice::STATUS_PENDING, Invoice::STATUS_DUE])->sum('balance_remaining');

        // Daily snapshot (cash basis + completion validation)
        $todayCashCollected = (float) Payment::whereDate('paid_at', today())->sum('amount_paid');
        $todayCashCount     = Payment::whereDate('paid_at', today())->count();
        $todayRevenue       = (float) Sale::whereDate('created_at', today())->sum('total_sale_price');

        // Recent invoices
        $recentInvoices = Invoice::query()
            ->latest('created_at')
            ->take(5)
            ->get(['id', 'invoice_number', 'customer_name', 'organization', 'total', 'invoice_date', 'status', 'amount_paid', 'balance_remaining', 'created_at']);

        // Recent payments
        $recentPayments = Payment::query()
            ->with('invoice:id,invoice_number,customer_name')
            ->latest('paid_at')
            ->take(5)
            ->get();

        return response()->json([
            'total_invoices'        => $totalInvoices,
            'cash_collected'        => $cashCollected,
            'earned_revenue'        => $earnedRevenue,
            'total_revenue'         => $earnedRevenue, // Backward-compatible alias.
            'total_sales'           => $totalSales,
            'total_sales_revenue'   => $earnedRevenue,
            'total_profit'          => $totalProfit,
            'total_payments'        => $cashCollected,
            'outstanding_invoices'  => $outstandingInvoices,
            'outstanding_amount'    => $outstandingAmount,
            'today_cash_collected'  => $todayCashCollected,
            'today_cash_count'      => $todayCashCount,
            'today_earned_revenue'  => $todayRevenue,
            'today_sales'           => $todayRevenue, // Backward-compatible alias.
            'today_count'           => $todayCashCount, // Backward-compatible alias.
            'recent_invoices'       => $recentInvoices,
            'recent_payments'       => $recentPayments,
        ]);
    }
}
