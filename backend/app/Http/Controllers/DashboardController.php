<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $userId = $user?->id;

        $invoiceBase = Invoice::query();
        if (($user?->role ?? 'admin') === 'admin') {
            $invoiceBase->where('created_by_user_id', $userId);
        }

        $paymentBase = Payment::query();
        if (($user?->role ?? 'admin') === 'admin') {
            $paymentBase->whereHas('invoice', function ($query) use ($userId): void {
                $query->where('created_by_user_id', $userId);
            });
        }

        $saleBase = Sale::query();
        if (($user?->role ?? 'admin') === 'admin') {
            $saleBase->whereHas('invoice', function ($query) use ($userId): void {
                $query->where('created_by_user_id', $userId);
            });
        }

        $totalInvoices = (clone $invoiceBase)->count();
        $cashCollected = (float) (clone $paymentBase)->sum('amount_paid');
        $earnedRevenue = (float) (clone $saleBase)->sum('total_sale_price');
        $totalProfit   = (float) (clone $saleBase)->sum('profit');

        // Volume and collections statistics
        $totalSales = (clone $saleBase)->count();
        $outstandingInvoices = (clone $invoiceBase)
            ->whereIn('status', [Invoice::STATUS_PENDING, Invoice::STATUS_DUE])
            ->count();
        $outstandingAmount = (float) (clone $invoiceBase)
            ->whereIn('status', [Invoice::STATUS_PENDING, Invoice::STATUS_DUE])
            ->sum('balance_remaining');

        // Daily snapshot (cash basis + completion validation)
        $todayCashCollected = (float) (clone $paymentBase)
            ->whereDate('paid_at', today())
            ->sum('amount_paid');
        $todayCashCount = (clone $paymentBase)
            ->whereDate('paid_at', today())
            ->count();
        $todayRevenue = (float) (clone $saleBase)
            ->whereDate('created_at', today())
            ->sum('total_sale_price');

        // Recent invoices
        $recentInvoices = (clone $invoiceBase)
            ->latest('created_at')
            ->take(5)
            ->get(['id', 'invoice_number', 'customer_name', 'organization', 'total', 'invoice_date', 'status', 'amount_paid', 'balance_remaining', 'created_at']);

        // Recent payments
        $recentPayments = (clone $paymentBase)
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
