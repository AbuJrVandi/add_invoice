<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OwnerApprovalController extends Controller
{
    /**
     * Read-only approval queue for owners.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Invoice::query()
            ->whereIn('status', [Invoice::STATUS_PENDING, Invoice::STATUS_DUE])
            ->latest('invoice_date');

        if ($request->boolean('overdue_only')) {
            $query->whereDate('due_date', '<', now()->toDateString())
                ->where('balance_remaining', '>', 0);
        }

        if ($request->boolean('partial_only')) {
            $query->where('amount_paid', '>', 0)
                ->where('balance_remaining', '>', 0);
        }

        $approvals = $query->paginate((int) $request->input('per_page', 15), [
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

        return response()->json($approvals);
    }
}
