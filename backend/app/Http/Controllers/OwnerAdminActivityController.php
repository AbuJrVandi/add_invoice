<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OwnerAdminActivityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $owner = $request->user();

        $admins = User::query()
            ->where('role', 'admin')
            ->where('managed_by_owner_id', $owner?->id)
            ->withCount([
                'createdInvoices as total_invoices',
                'createdInvoices as pending_invoices' => fn ($query) => $query->where('status', Invoice::STATUS_PENDING),
                'createdInvoices as due_invoices' => fn ($query) => $query->where('status', Invoice::STATUS_DUE),
                'createdInvoices as completed_invoices' => fn ($query) => $query
                    ->whereIn('status', [Invoice::STATUS_COMPLETED, Invoice::STATUS_PAID]),
                'createdPayments as total_receipts',
            ])
            ->withSum([
                'createdInvoices as outstanding_balance' => fn ($query) => $query
                    ->whereNotIn('status', [Invoice::STATUS_COMPLETED, Invoice::STATUS_PAID]),
            ], 'balance_remaining')
            ->withMax('createdInvoices as last_invoice_created_at', 'created_at')
            ->withMax('createdPayments as last_receipt_created_at', 'paid_at')
            ->orderByDesc('total_invoices')
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'email',
                'is_active',
                'created_at',
            ])
            ->map(fn (User $admin): array => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'is_active' => (bool) ($admin->is_active ?? true),
                'created_at' => $admin->created_at?->toISOString(),
                'metrics' => [
                    'total_invoices' => (int) ($admin->total_invoices ?? 0),
                    'pending_invoices' => (int) ($admin->pending_invoices ?? 0),
                    'due_invoices' => (int) ($admin->due_invoices ?? 0),
                    'completed_invoices' => (int) ($admin->completed_invoices ?? 0),
                    'total_receipts' => (int) ($admin->total_receipts ?? 0),
                    'outstanding_balance' => round((float) ($admin->outstanding_balance ?? 0), 2),
                    'last_invoice_created_at' => $admin->last_invoice_created_at,
                    'last_receipt_created_at' => $admin->last_receipt_created_at,
                ],
            ])
            ->values();

        return response()->json([
            'generated_at' => now()->toIso8601String(),
            'data' => $admins,
        ]);
    }

    public function show(Request $request, User $user): JsonResponse
    {
        $owner = $request->user();

        if (! $this->isManagedAdminForOwner($owner?->id, $user)) {
            return response()->json([
                'message' => 'Admin account not found for this owner.',
            ], 404);
        }

        $validated = $request->validate([
            'invoices_page' => ['nullable', 'integer', 'min:1'],
            'receipts_page' => ['nullable', 'integer', 'min:1'],
            'invoices_per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'receipts_per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $invoicesPerPage = (int) ($validated['invoices_per_page'] ?? 15);
        $receiptsPerPage = (int) ($validated['receipts_per_page'] ?? 15);
        $invoicesPage = (int) ($validated['invoices_page'] ?? 1);
        $receiptsPage = (int) ($validated['receipts_page'] ?? 1);

        $invoices = Invoice::query()
            ->where('created_by_user_id', $user->id)
            ->latest('created_at')
            ->paginate(
                $invoicesPerPage,
                [
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
                    'created_by_user_id',
                    'created_at',
                ],
                'invoices_page',
                $invoicesPage
            );

        $receipts = Payment::query()
            ->with('invoice:id,invoice_number,customer_name,organization,total,status')
            ->where('created_by', $user->id)
            ->latest('paid_at')
            ->paginate(
                $receiptsPerPage,
                [
                    'id',
                    'invoice_id',
                    'receipt_number',
                    'amount_paid',
                    'payment_method',
                    'paid_at',
                    'created_by',
                    'notes',
                    'created_at',
                ],
                'receipts_page',
                $receiptsPage
            );

        return response()->json([
            'admin' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => (bool) ($user->is_active ?? true),
                'created_at' => $user->created_at?->toISOString(),
            ],
            'invoices' => $invoices,
            'receipts' => $receipts,
        ]);
    }

    private function isManagedAdminForOwner(?int $ownerId, User $admin): bool
    {
        if (! $ownerId) {
            return false;
        }

        if ($admin->role !== 'admin') {
            return false;
        }

        return (int) ($admin->managed_by_owner_id ?? 0) === $ownerId;
    }
}
