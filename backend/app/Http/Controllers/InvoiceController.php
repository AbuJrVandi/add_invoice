<?php

namespace App\Http\Controllers;

use App\Http\Requests\InvoiceFilterRequest;
use App\Http\Requests\StoreInvoiceRequest;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoicePdfSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
            $computed = $this->computeInvoiceTotals($validated);
            $computedItems = $computed['items'];
            $subtotal = $computed['subtotal'];
            $tax = $computed['tax'];
            $total = $computed['total'];

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
                'status' => Invoice::STATUS_PENDING,
                'amount_paid' => 0,
                'balance_remaining' => $total,
            ]);

            $invoice->items()->createMany($computedItems);

            $filename = 'invoice-'.$invoice->invoice_number.'-'.time().'.pdf';
            $relativePath = 'invoices/'.$filename;

            $companyData = $this->buildCompanyData();

            $pdf = Pdf::loadView('pdf.invoice', [
                'invoice' => $invoice->fresh('items'),
                'company' => $companyData,
            ]);

            $paper = $this->resolveInvoicePaper($invoice->fresh('items'));
            if (is_array($paper['size'])) {
                $pdf->setPaper($paper['size']);
            } else {
                $pdf->setPaper($paper['size'], $paper['orientation']);
            }

            Storage::disk('public')->put($relativePath, $pdf->output());

            $invoice->update(['pdf_path' => 'storage/'.$relativePath]);

            return $invoice->fresh('items');
        });

        return response()->json([
            'message' => 'Invoice created successfully.',
            'data' => [
                ...$invoice->toArray(),
                'pdf_url' => url('/api/invoices/'.$invoice->id.'/pdf'),
            ],
        ], 201);
    }

    /**
     * Build a PDF preview from draft payload (no persistence).
     * The same template and totals logic are used as the final stored invoice.
     */
    public function previewPdf(StoreInvoiceRequest $request): Response
    {
        $validated = $request->validated();
        $computed = $this->computeInvoiceTotals($validated);

        $invoiceNumber = trim((string) ($validated['invoice_number'] ?? ''));
        if ($invoiceNumber === '') {
            $invoiceNumber = $this->buildUniqueInvoiceNumber();
        }

        $previewInvoice = new Invoice([
            'invoice_number' => $invoiceNumber,
            'customer_name' => $validated['customer_name'],
            'organization' => $validated['organization'],
            'bill_to' => $validated['bill_to'],
            'ship_to' => $validated['ship_to'],
            'invoice_date' => $validated['invoice_date'],
            'due_date' => $validated['due_date'],
            'po_number' => $validated['po_number'] ?? null,
            'subtotal' => $computed['subtotal'],
            'tax' => $computed['tax'],
            'total' => $computed['total'],
            'status' => Invoice::STATUS_PENDING,
            'amount_paid' => 0,
            'balance_remaining' => $computed['total'],
        ]);

        $previewInvoice->setRelation(
            'items',
            collect($computed['items'])->map(fn (array $item): InvoiceItem => new InvoiceItem($item))
        );

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $previewInvoice,
            'company' => $this->buildCompanyData(),
        ]);

        $paper = $this->resolveInvoicePaper($previewInvoice);
        if (is_array($paper['size'])) {
            $pdf->setPaper($paper['size']);
        } else {
            $pdf->setPaper($paper['size'], $paper['orientation']);
        }

        $filename = 'invoice-preview-'.$invoiceNumber.'.pdf';

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'Cache-Control' => 'private, max-age=0, must-revalidate',
        ]);
    }

    public function pdf(Invoice $invoice): BinaryFileResponse|JsonResponse
    {
        if (! $invoice->pdf_path) {
            return response()->json(['message' => 'Invoice PDF is not available.'], 404);
        }

        $relativePath = str_starts_with($invoice->pdf_path, 'storage/')
            ? substr($invoice->pdf_path, strlen('storage/'))
            : ltrim($invoice->pdf_path, '/');

        if (! Storage::disk('public')->exists($relativePath)) {
            return response()->json(['message' => 'Invoice PDF file was not found on the server.'], 404);
        }

        $filename = 'invoice-'.$invoice->invoice_number.'.pdf';

        return response()->file(Storage::disk('public')->path($relativePath), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'Cache-Control' => 'private, max-age=0, must-revalidate',
        ]);
    }

    public function show(Invoice $invoice): JsonResponse
    {
        return response()->json($invoice->load('items'));
    }

    public function destroy(Invoice $invoice): JsonResponse
    {
        if ($invoice->payments()->exists() || $invoice->hasSale() || $invoice->isFullyPaid()) {
            return response()->json([
                'message' => 'This invoice has financial history and cannot be deleted.',
            ], 422);
        }

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

    /**
     * Build the company data array for the PDF template.
     * Logo is always included automatically — uses uploaded setting or falls back to the default CIRQON logo.
     */
    private function buildCompanyData(): array
    {
        $settings = InvoicePdfSetting::query()->first();

        // Determine logo path — always include the logo
        $logoPath = public_path('assets/company-logo.png');

        if ($settings?->logo_path) {
            $uploadedLogoPath = storage_path('app/public/'.$settings->logo_path);
            if (file_exists($uploadedLogoPath)) {
                $logoPath = $uploadedLogoPath;
            }
        }

        // Fallback: if the png doesn't exist, try jpeg
        if (! file_exists($logoPath)) {
            $jpegFallback = public_path('assets/company-logo.jpeg');
            if (file_exists($jpegFallback)) {
                $logoPath = $jpegFallback;
            }
        }

        // Determine signature path
        $signaturePath = public_path('assets/default-signature.png');
        if ($settings?->signature_path) {
            $uploadedSigPath = storage_path('app/public/'.$settings->signature_path);
            if (file_exists($uploadedSigPath)) {
                $signaturePath = $uploadedSigPath;
            }
        }

        return [
            'name' => $settings?->company_name ?? 'CIRQON Electronics',
            'address_lines' => [
                'No. 4 Light-Foot Boston Street',
                'Via Radwon Street, Freetown',
            ],
            'phone' => '+232 74 141141 | +232 79 576950',
            'logo' => $logoPath,
            'signature' => $signaturePath,
            'issuer_name' => $settings?->issuer_name ?? 'CEO- Vandi Abu',
            'terms' => [
                'Payment is due within 15 days',
                'Please make checks payable to: '.($settings?->company_name ?? 'CIRQON Electronics').'.',
            ],
            // Payment details matching the reference PDF
            'bank' => 'UBA',
            'account_name' => 'Wickburn Services SL LTD',
            'account_no' => '5401-1003-000922-9',
            'iban' => '010401100300092257',
            'swift_code' => 'UNAFSLFR',
            'contact_person' => 'Vandi Abu',
            'contact_email' => 'Jamesericksoncole57@gmail.com',
        ];
    }

    /**
     * Compute canonical item amounts and totals for preview + persistence consistency.
     */
    private function computeInvoiceTotals(array $validated): array
    {
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
        $discount = round((float) ($validated['tax'] ?? 0), 2);

        if ($discount > $subtotal) {
            throw ValidationException::withMessages([
                'tax' => ['Discount cannot be greater than subtotal.'],
            ]);
        }

        // Accounting rule: discount reduces the invoice total.
        $total = round($subtotal - $discount, 2);

        return [
            'items' => $computedItems,
            'subtotal' => $subtotal,
            'tax' => $discount,
            'total' => $total,
        ];
    }

    /**
     * Keep invoices on a single page by dynamically expanding paper size.
     * - <= 3 items: standard A4 portrait
     * - > 3 items: start from A3 height and grow further when needed
     */
    private function resolveInvoicePaper(Invoice $invoice): array
    {
        $items = $invoice->relationLoaded('items')
            ? $invoice->items
            : $invoice->items()->get();

        $itemCount = $items->count();
        if ($itemCount <= 3) {
            return [
                'size' => 'a4',
                'orientation' => 'portrait',
            ];
        }

        // A3 portrait base in points: width 842, height 1191
        $baseWidth = 842;
        $baseHeight = 1191;

        // Estimate vertical demand from item count + description density.
        $estimatedDescriptionLines = $items->sum(
            fn (InvoiceItem $item): int => max((int) ceil(mb_strlen((string) $item->description) / 85), 1)
        );

        $estimatedRows = (int) $itemCount + (int) $estimatedDescriptionLines;
        $extraHeight = max(0, $estimatedRows - 14) * 24;
        $targetHeight = $baseHeight + $extraHeight;

        return [
            'size' => [0, 0, $baseWidth, $targetHeight],
            'orientation' => null,
        ];
    }

    private function buildUniqueInvoiceNumber(): string
    {
        do {
            $candidate = 'INV-'.now()->format('Ymd').'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        } while (Invoice::query()->where('invoice_number', $candidate)->exists());

        return $candidate;
    }
}
