<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class OwnerAnalyticsController extends Controller
{
    /**
     * Read-only owner analytics cards and live activity feed.
     *
     * Financial table contract:
     * - payments: liquidity and receipts
     * - sales: earned revenue/profit snapshots
     * - invoices: obligations and invoice volume
     */
    public function __invoke(): JsonResponse
    {
        $today = Carbon::today();
        $yesterday = $today->copy()->subDay();

        $startOfMonth = $today->copy()->startOfMonth();
        $startOfLastMonth = $today->copy()->subMonthNoOverflow()->startOfMonth();
        $endOfLastMonth = $today->copy()->subMonthNoOverflow()->endOfMonth();

        $startOfLast30Days = $today->copy()->subDays(29);
        $startOfPrevious30Days = $today->copy()->subDays(59);
        $endOfPrevious30Days = $today->copy()->subDays(30);

        $startOfLast7Days = $today->copy()->subDays(6);
        $startOfPrevious7Days = $today->copy()->subDays(13);
        $endOfPrevious7Days = $today->copy()->subDays(7);

        $seriesDates = collect(range(6, 0))
            ->map(fn (int $daysBack): string => $today->copy()->subDays($daysBack)->toDateString())
            ->values();

        // Section 1: Liquidity (payments)
        $cashCollectedToday = (float) Payment::query()
            ->whereDate('paid_at', $today->toDateString())
            ->sum('amount_paid');

        $cashCollectedYesterday = (float) Payment::query()
            ->whereDate('paid_at', $yesterday->toDateString())
            ->sum('amount_paid');

        $cashCollectedThisMonth = (float) Payment::query()
            ->whereBetween('paid_at', [$startOfMonth, $today->copy()->endOfDay()])
            ->sum('amount_paid');

        $cashCollectedLastMonth = (float) Payment::query()
            ->whereBetween('paid_at', [$startOfLastMonth, $endOfLastMonth->copy()->endOfDay()])
            ->sum('amount_paid');

        $cashCollectedLast30Days = (float) Payment::query()
            ->whereDate('paid_at', '>=', $startOfLast30Days->toDateString())
            ->whereDate('paid_at', '<=', $today->toDateString())
            ->sum('amount_paid');

        $averageDailyCollection = $cashCollectedLast30Days / 30;

        // Section 2: Performance (sales)
        $earnedRevenue = (float) Sale::query()->sum('total_sale_price');
        $profit = (float) Sale::query()->sum('profit');
        $salesCount = Sale::query()->count();

        $profitMargin = $earnedRevenue > 0
            ? (($profit / $earnedRevenue) * 100)
            : 0.0;

        $averageProfitPerSale = $salesCount > 0
            ? ($profit / $salesCount)
            : 0.0;

        $revenueLast30Days = (float) Sale::query()
            ->whereDate('created_at', '>=', $startOfLast30Days->toDateString())
            ->whereDate('created_at', '<=', $today->toDateString())
            ->sum('total_sale_price');

        $revenuePrevious30Days = (float) Sale::query()
            ->whereDate('created_at', '>=', $startOfPrevious30Days->toDateString())
            ->whereDate('created_at', '<=', $endOfPrevious30Days->toDateString())
            ->sum('total_sale_price');

        $profitLast30Days = (float) Sale::query()
            ->whereDate('created_at', '>=', $startOfLast30Days->toDateString())
            ->whereDate('created_at', '<=', $today->toDateString())
            ->sum('profit');

        $profitPrevious30Days = (float) Sale::query()
            ->whereDate('created_at', '>=', $startOfPrevious30Days->toDateString())
            ->whereDate('created_at', '<=', $endOfPrevious30Days->toDateString())
            ->sum('profit');

        // Section 3: Risk (invoices)
        $overdueInvoices = Invoice::query()
            ->whereDate('due_date', '<', $today->toDateString())
            ->where('status', '!=', Invoice::STATUS_COMPLETED)
            ->count();

        $outstandingBalance = (float) Invoice::query()
            ->where('status', '!=', Invoice::STATUS_COMPLETED)
            ->sum('balance_remaining');

        $partiallyPaidInvoices = Invoice::query()
            ->where('status', Invoice::STATUS_DUE)
            ->count();

        // Operational overview
        $invoiceStatusCounts = [
            'draft' => Invoice::query()->where('status', Invoice::STATUS_DRAFT)->count(),
            'pending' => Invoice::query()->where('status', Invoice::STATUS_PENDING)->count(),
            'due' => Invoice::query()->where('status', Invoice::STATUS_DUE)->count(),
            'completed' => Invoice::query()->where('status', Invoice::STATUS_COMPLETED)->count(),
        ];

        $totalInvoices = array_sum($invoiceStatusCounts);
        $totalReceipts = Payment::query()->count();
        $invoicesWithReceipts = Invoice::query()->where('amount_paid', '>', 0)->count();

        $invoiceCompletionRate = $totalInvoices > 0
            ? ($invoiceStatusCounts['completed'] / $totalInvoices) * 100
            : 0.0;

        $receiptCoverageRate = $totalInvoices > 0
            ? ($invoicesWithReceipts / $totalInvoices) * 100
            : 0.0;

        $averageReceiptAmount = (float) Payment::query()->avg('amount_paid');

        $invoicesCreatedThisMonth = Invoice::query()
            ->whereDate('created_at', '>=', $startOfMonth->toDateString())
            ->whereDate('created_at', '<=', $today->toDateString())
            ->count();

        $receiptsIssuedThisMonth = Payment::query()
            ->whereDate('paid_at', '>=', $startOfMonth->toDateString())
            ->whereDate('paid_at', '<=', $today->toDateString())
            ->count();

        $invoicesCreatedLast7Days = Invoice::query()
            ->whereDate('created_at', '>=', $startOfLast7Days->toDateString())
            ->whereDate('created_at', '<=', $today->toDateString())
            ->count();

        $invoicesCreatedPrevious7Days = Invoice::query()
            ->whereDate('created_at', '>=', $startOfPrevious7Days->toDateString())
            ->whereDate('created_at', '<=', $endOfPrevious7Days->toDateString())
            ->count();

        $receiptsIssuedLast7Days = Payment::query()
            ->whereDate('paid_at', '>=', $startOfLast7Days->toDateString())
            ->whereDate('paid_at', '<=', $today->toDateString())
            ->count();

        $receiptsIssuedPrevious7Days = Payment::query()
            ->whereDate('paid_at', '>=', $startOfPrevious7Days->toDateString())
            ->whereDate('paid_at', '<=', $endOfPrevious7Days->toDateString())
            ->count();

        // Time series
        $paymentSeriesMap = Payment::query()
            ->selectRaw('DATE(paid_at) as day, SUM(amount_paid) as total')
            ->whereDate('paid_at', '>=', $seriesDates->first())
            ->whereDate('paid_at', '<=', $seriesDates->last())
            ->groupBy(DB::raw('DATE(paid_at)'))
            ->pluck('total', 'day');

        $receiptCountSeriesMap = Payment::query()
            ->selectRaw('DATE(paid_at) as day, COUNT(*) as total')
            ->whereDate('paid_at', '>=', $seriesDates->first())
            ->whereDate('paid_at', '<=', $seriesDates->last())
            ->groupBy(DB::raw('DATE(paid_at)'))
            ->pluck('total', 'day');

        $invoiceCountSeriesMap = Invoice::query()
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->whereDate('created_at', '>=', $seriesDates->first())
            ->whereDate('created_at', '<=', $seriesDates->last())
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('total', 'day');

        $revenueSeriesMap = Sale::query()
            ->selectRaw('DATE(created_at) as day, SUM(total_sale_price) as total')
            ->whereDate('created_at', '>=', $seriesDates->first())
            ->whereDate('created_at', '<=', $seriesDates->last())
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('total', 'day');

        $profitSeriesMap = Sale::query()
            ->selectRaw('DATE(created_at) as day, SUM(profit) as total')
            ->whereDate('created_at', '>=', $seriesDates->first())
            ->whereDate('created_at', '<=', $seriesDates->last())
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('total', 'day');

        $paymentMethodCounts = Payment::query()
            ->selectRaw('payment_method, COUNT(*) as total')
            ->groupBy('payment_method')
            ->pluck('total', 'payment_method');

        $activityLimit = 250;

        $recentInvoices = Invoice::query()
            ->latest('created_at')
            ->take($activityLimit)
            ->get([
                'id',
                'invoice_number',
                'customer_name',
                'organization',
                'invoice_date',
                'due_date',
                'status',
                'total',
                'amount_paid',
                'balance_remaining',
                'created_at',
            ]);

        $recentReceipts = Payment::query()
            ->with('invoice:id,invoice_number,customer_name,organization,status,total,balance_remaining')
            ->latest('paid_at')
            ->take($activityLimit)
            ->get([
                'id',
                'invoice_id',
                'receipt_number',
                'amount_paid',
                'payment_method',
                'paid_at',
                'notes',
            ]);

        return response()->json([
            'generated_at' => now()->toIso8601String(),
            'overview' => [
                'total_invoices' => $totalInvoices,
                'total_receipts' => $totalReceipts,
                'draft_invoices' => $invoiceStatusCounts['draft'],
                'pending_invoices' => $invoiceStatusCounts['pending'],
                'due_invoices' => $invoiceStatusCounts['due'],
                'completed_invoices' => $invoiceStatusCounts['completed'],
                'overdue_invoices' => $overdueInvoices,
                'invoice_completion_rate' => round($invoiceCompletionRate, 2),
                'receipt_coverage_rate' => round($receiptCoverageRate, 2),
                'average_receipt_amount' => round($averageReceiptAmount, 2),
                'invoices_created_this_month' => $invoicesCreatedThisMonth,
                'receipts_issued_this_month' => $receiptsIssuedThisMonth,
            ],
            'liquidity' => [
                'today' => round($cashCollectedToday, 2),
                'this_month' => round($cashCollectedThisMonth, 2),
                'avg_last_30_days' => round($averageDailyCollection, 2),
            ],
            'performance' => [
                'earned_revenue' => round($earnedRevenue, 2),
                'profit' => round($profit, 2),
                'profit_margin' => round($profitMargin, 2),
                'avg_profit_per_sale' => round($averageProfitPerSale, 2),
            ],
            'risk' => [
                'outstanding_balance' => round($outstandingBalance, 2),
                'overdue_invoices' => $overdueInvoices,
                'partially_paid_invoices' => $partiallyPaidInvoices,
            ],
            'trends' => [
                'liquidity' => [
                    'today_vs_yesterday' => $this->percentChange($cashCollectedToday, $cashCollectedYesterday),
                    'this_month_vs_last_month' => $this->percentChange($cashCollectedThisMonth, $cashCollectedLastMonth),
                ],
                'performance' => [
                    'revenue_30_days' => $this->percentChange($revenueLast30Days, $revenuePrevious30Days),
                    'profit_30_days' => $this->percentChange($profitLast30Days, $profitPrevious30Days),
                ],
                'operations' => [
                    'invoices_7_days' => $this->percentChange((float) $invoicesCreatedLast7Days, (float) $invoicesCreatedPrevious7Days),
                    'receipts_7_days' => $this->percentChange((float) $receiptsIssuedLast7Days, (float) $receiptsIssuedPrevious7Days),
                ],
            ],
            'series' => [
                'labels' => $seriesDates->map(fn (string $day): string => Carbon::parse($day)->format('d M'))->values(),
                'cash' => $seriesDates->map(fn (string $day): float => round((float) ($paymentSeriesMap[$day] ?? 0), 2))->values(),
                'revenue' => $seriesDates->map(fn (string $day): float => round((float) ($revenueSeriesMap[$day] ?? 0), 2))->values(),
                'profit' => $seriesDates->map(fn (string $day): float => round((float) ($profitSeriesMap[$day] ?? 0), 2))->values(),
            ],
            'weekly_activity' => [
                'labels' => $seriesDates->map(fn (string $day): string => Carbon::parse($day)->format('D'))->values(),
                'invoices_created' => $seriesDates->map(fn (string $day): int => (int) ($invoiceCountSeriesMap[$day] ?? 0))->values(),
                'receipts_issued' => $seriesDates->map(fn (string $day): int => (int) ($receiptCountSeriesMap[$day] ?? 0))->values(),
                'collected_cash' => $seriesDates->map(fn (string $day): float => round((float) ($paymentSeriesMap[$day] ?? 0), 2))->values(),
            ],
            'distributions' => [
                'invoice_status' => [
                    ['label' => 'Pending', 'key' => 'pending', 'value' => $invoiceStatusCounts['pending']],
                    ['label' => 'Due', 'key' => 'due', 'value' => $invoiceStatusCounts['due']],
                    ['label' => 'Completed', 'key' => 'completed', 'value' => $invoiceStatusCounts['completed']],
                    ['label' => 'Draft', 'key' => 'draft', 'value' => $invoiceStatusCounts['draft']],
                ],
                'payment_methods' => [
                    ['label' => 'Cash', 'key' => 'cash', 'value' => (int) ($paymentMethodCounts['cash'] ?? 0)],
                    ['label' => 'Transfer', 'key' => 'transfer', 'value' => (int) ($paymentMethodCounts['transfer'] ?? 0)],
                    ['label' => 'Mobile Money', 'key' => 'mobile_money', 'value' => (int) ($paymentMethodCounts['mobile_money'] ?? 0)],
                    ['label' => 'Card', 'key' => 'card', 'value' => (int) ($paymentMethodCounts['card'] ?? 0)],
                ],
            ],
            'recent' => [
                'invoices' => $recentInvoices,
                'receipts' => $recentReceipts,
            ],
        ]);
    }

    private function percentChange(float $current, float $previous): ?float
    {
        if ($previous === 0.0) {
            return $current > 0 ? 100.0 : null;
        }

        return round((($current - $previous) / abs($previous)) * 100, 2);
    }
}
