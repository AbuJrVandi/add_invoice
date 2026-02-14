<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $totalInvoices = Invoice::count();
        $totalRevenue = (float) Invoice::sum('total');
        $recentInvoices = Invoice::query()
            ->latest('created_at')
            ->take(5)
            ->get(['id', 'invoice_number', 'customer_name', 'organization', 'total', 'invoice_date', 'created_at']);

        return response()->json([
            'total_invoices' => $totalInvoices,
            'total_revenue' => $totalRevenue,
            'recent_invoices' => $recentInvoices,
        ]);
    }
}
