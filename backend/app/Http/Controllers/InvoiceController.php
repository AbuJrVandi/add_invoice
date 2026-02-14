<?php

namespace App\Http\Controllers;

use App\Http\Requests\InvoiceFilterRequest;
use App\Http\Requests\StoreInvoiceRequest;
use App\Models\Invoice;
use App\Models\InvoicePdfSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InvoiceController extends Controller
{
    public function nextNumber(): JsonResponse
    {
        return response()->json([
            'invoice_number' => $this->buildUniqueInvoiceNumber(),
        ]);
    }

    public function index(InvoiceFilterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $query = Invoice::query()->with('items')->latest('invoice_date');

        if (! empty($validated['invoice_number'])) {
            $invoiceNumber = trim($validated['invoice_number']);
            $query->where('invoice_number', 'like', '%'.$invoiceNumber.'%');
        }

        if (! empty($validated['customer_name'])) {
            $customerName = trim($validated['customer_name']);
            $query->whereRaw('LOWER(customer_name) like ?', ['%'.strtolower($customerName).'%']);
        }

        if (! empty($validated['organization'])) {
            $organization = trim($validated['organization']);
            $query->whereRaw('LOWER(organization) like ?', ['%'.strtolower($organization).'%']);
        }

        if (! empty($validated['date'])) {
            $query->whereDate('invoice_date', $validated['date']);
        }

        $invoices = $query->paginate($validated['per_page'] ?? 10);

        return response()->json($invoices);
    }

    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $invoice = DB::transaction(function () use ($validated) {
            $computedItems = [];
            $subtotal = 0.0;

            foreach ($validated['items'] as $item) {
                $quantity = (int) $item['quantity'];
                $unitPrice = round((float) $item['unit_price'], 2);
                $amount = round($quantity * $unitPrice, 2);
                $subtotal += $amount;

                $computedItems[] = [
                    'description' => $item['description'],
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'amount' => $amount,
                ];
            }

            $subtotal = round($subtotal, 2);
            $tax = round((float) ($validated['tax'] ?? 0), 2);
            $total = round($subtotal + $tax, 2);

            $requestedNumber = $validated['invoice_number'] ?? null;
            $invoiceNumber = $requestedNumber && ! Invoice::query()->where('invoice_number', $requestedNumber)->exists()
                ? $requestedNumber
                : $this->buildUniqueInvoiceNumber();

            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'customer_name' => $validated['customer_name'],
                'organization' => $validated['organization'],
                'bill_to' => $validated['bill_to'],
                'ship_to' => $validated['ship_to'],
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'],
                'po_number' => $validated['po_number'] ?? null,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
                'pdf_path' => null,
            ]);

            $invoice->items()->createMany($computedItems);

            $filename = 'invoice-'.$invoice->invoice_number.'-'.time().'.pdf';
            $relativePath = 'invoices/'.$filename;
            $settings = InvoicePdfSetting::query()->first();

            $pdf = Pdf::loadView('pdf.invoice', [
                'invoice' => $invoice->fresh('items'),
                'company' => [
                    'name' => $settings?->company_name ?? config('app.name', 'East Repair Inc.'),
                    'address_lines' => [
                        '1912 Harvest Lane',
                        'New York, NY 12210',
                    ],
                    'logo' => $settings?->logo_path ? storage_path('app/public/'.$settings->logo_path) : public_path('assets/company-logo.png'),
                    'signature' => $settings?->signature_path ? storage_path('app/public/'.$settings->signature_path) : public_path('assets/default-signature.png'),
                    'issuer_name' => $settings?->issuer_name ?? 'Administrator',
                    'terms' => [
                        'Payment is due within 15 days',
                        'Please make checks payable to: '.($settings?->company_name ?? 'East Repair Inc.').'.',
                    ],
                ],
            ])->setPaper('a4', 'portrait');

            Storage::disk('public')->put($relativePath, $pdf->output());

            $invoice->update(['pdf_path' => 'storage/'.$relativePath]);

            return $invoice->fresh('items');
        });

        return response()->json([
            'message' => 'Invoice created successfully.',
            'data' => [
                ...$invoice->toArray(),
                'pdf_url' => $invoice->pdf_path ? url($invoice->pdf_path) : null,
            ],
        ], 201);
    }

    public function show(Invoice $invoice): JsonResponse
    {
        return response()->json($invoice->load('items'));
    }

    public function destroy(Invoice $invoice): JsonResponse
    {
        DB::transaction(function () use ($invoice): void {
            if ($invoice->pdf_path) {
                $relativePath = str_replace('storage/', '', $invoice->pdf_path);
                Storage::disk('public')->delete($relativePath);
            }

            $invoice->items()->delete();
            $invoice->delete();
        });

        return response()->json(['message' => 'Invoice deleted successfully.']);
    }

    private function buildUniqueInvoiceNumber(): string
    {
        do {
            $candidate = 'INV-'.now()->format('Ymd').'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        } while (Invoice::query()->where('invoice_number', $candidate)->exists());

        return $candidate;
    }
}
