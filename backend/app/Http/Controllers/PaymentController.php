<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    /**
     * List payments with optional filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Payment::query()
            ->with(['invoice:id,invoice_number,customer_name,organization,total,status,amount_paid,balance_remaining'])
            ->latest('paid_at');

        if ($request->filled('invoice_number')) {
            $query->whereHas('invoice', function ($q) use ($request) {
                $q->where('invoice_number', 'like', '%' . trim($request->input('invoice_number')) . '%');
            });
        }

        if ($request->filled('customer_name')) {
            $query->whereHas('invoice', function ($q) use ($request) {
                $q->whereRaw('LOWER(customer_name) like ?', ['%' . strtolower(trim($request->input('customer_name'))) . '%']);
            });
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->input('payment_method'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('paid_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('paid_at', '<=', $request->input('date_to'));
        }

        return response()->json($query->paginate($request->input('per_page', 15)));
    }

    /**
     * Search invoices for the payment page.
     * Returns invoices that can be paid (pending or due status).
     */
    public function searchInvoices(Request $request): JsonResponse
    {
        $query = Invoice::query()
            ->with('items')
            ->whereIn('status', [Invoice::STATUS_PENDING, Invoice::STATUS_DUE]);

        if ($request->filled('invoice_number')) {
            $query->where('invoice_number', 'like', '%' . trim($request->input('invoice_number')) . '%');
        }

        if ($request->filled('customer_name')) {
            $term = strtolower(trim($request->input('customer_name')));
            $query->where(function ($q) use ($term) {
                $q->whereRaw('LOWER(customer_name) like ?', ['%' . $term . '%'])
                  ->orWhereRaw('LOWER(bill_to) like ?', ['%' . $term . '%']);
            });
        }

        if ($request->filled('organization')) {
            $query->whereRaw('LOWER(organization) like ?', ['%' . strtolower(trim($request->input('organization'))) . '%']);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('invoice_date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('invoice_date', '<=', $request->input('date_to'));
        }

        $invoices = $query->latest('invoice_date')->paginate($request->input('per_page', 15));

        return response()->json($invoices);
    }

    /**
     * Record a payment for an invoice.
     *
     * Business rules:
     * - If amount_paid < balance_remaining: status = 'due' (partial)
     * - If amount_paid >= balance_remaining: status = 'completed', create sale, balance = 0
     * - Prevents duplicate sale records
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'invoice_id'     => 'required|exists:invoices,id',
            'amount_paid'    => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,transfer,mobile_money,card',
            'notes'          => 'nullable|string|max:500',
        ]);

        $invoice = Invoice::query()->findOrFail($validated['invoice_id']);

        // Guard: already completed
        if ($invoice->isFullyPaid()) {
            return response()->json([
                'message' => 'This invoice has already been fully paid.',
            ], 422);
        }

        $result = DB::transaction(function () use ($invoice, $validated, $request) {
            $amountPaying   = round((float) $validated['amount_paid'], 2);
            $currentBalance = round((float) $invoice->balance_remaining, 2);

            // Cap payment at balance
            if ($amountPaying > $currentBalance) {
                $amountPaying = $currentBalance;
            }

            $newAmountPaid       = round((float) $invoice->amount_paid + $amountPaying, 2);
            $newBalanceRemaining = round($currentBalance - $amountPaying, 2);

            // Create payment record
            $payment = Payment::create([
                'invoice_id'     => $invoice->id,
                'receipt_number' => Payment::generateReceiptNumber(),
                'amount_paid'    => $amountPaying,
                'payment_method' => $validated['payment_method'],
                'paid_at'        => now(),
                'created_by'     => $request->user()?->id,
                'notes'          => $validated['notes'] ?? null,
            ]);

            // Determine new status
            if ($newBalanceRemaining <= 0) {
                $newStatus           = Invoice::STATUS_COMPLETED;
                $newBalanceRemaining = 0;
                $paidAt              = now();
            } else {
                $newStatus = Invoice::STATUS_DUE;
                $paidAt    = null;
            }

            // Update invoice
            $invoice->update([
                'amount_paid'       => $newAmountPaid,
                'balance_remaining' => $newBalanceRemaining,
                'status'            => $newStatus,
                'paid_at'           => $paidAt,
            ]);

            // If fully paid, create sale record (only if not already created)
            $sale = null;
            if ($newStatus === Invoice::STATUS_COMPLETED && !$invoice->hasSale()) {
                // Financial snapshot is frozen at completion time.
                // Never recompute this later from invoice items/products.
                $totalSalePrice = round((float) $invoice->total, 2);
                $totalCostPrice = 0.00;
                $profit         = round($totalSalePrice - $totalCostPrice, 2);

                $sale = Sale::create([
                    'invoice_id'       => $invoice->id,
                    'payment_id'       => $payment->id,
                    'total_cost_price' => $totalCostPrice,
                    'total_sale_price' => $totalSalePrice,
                    'profit'           => $profit,
                ]);
            }

            return [
                'payment' => $payment->fresh('invoice'),
                'invoice' => $invoice->fresh(['items', 'payments']),
                'sale'    => $sale,
            ];
        });

        return response()->json([
            'message' => $result['invoice']->status === Invoice::STATUS_COMPLETED
                ? 'Payment recorded. Invoice is now fully paid!'
                : 'Partial payment recorded. Balance remaining: NLe ' . number_format((float) $result['invoice']->balance_remaining, 2),
            'data'    => $result,
        ], 201);
    }

    /**
     * Show a single payment with its invoice.
     */
    public function show(Payment $payment): JsonResponse
    {
        return response()->json(
            $payment->load(['invoice.items', 'creator:id,name,email'])
        );
    }

    /**
     * Get receipt view for printing.
     */
    public function receipt(Payment $payment)
    {
        $payment->load(['invoice.items', 'creator:id,name,email']);

        return view('print.receipt', [
            'receipt_number'  => $payment->receipt_number,
            'payment_method'  => $payment->payment_method,
            'amount_paid'     => $payment->amount_paid,
            'paid_at'         => $payment->paid_at,
            'paid_at_display' => $payment->paid_at->format('d/m/Y H:i'),
            'notes'           => $payment->notes,
            'invoice'         => $payment->invoice,
            'company'         => [
                'name'    => 'CIRQON Electronics',
                'address' => 'No. 4 Light-Foot Boston Street',
                'phone'   => '+232 74 141141 | +232 79 576950',
                'email'   => 'Jamesericksoncole57@gmail.com',
                'logo'    => $this->receiptLogoDataUri(),
            ],
        ]);
    }

    private function receiptLogoDataUri(): ?string
    {
        $pngPath = public_path('assets/company-logo.png');
        if (file_exists($pngPath)) {
            return 'data:image/png;base64,' . base64_encode(file_get_contents($pngPath));
        }

        $jpegPath = public_path('assets/company-logo.jpeg');
        if (file_exists($jpegPath)) {
            return 'data:image/jpeg;base64,' . base64_encode(file_get_contents($jpegPath));
        }

        return null;
    }
}
