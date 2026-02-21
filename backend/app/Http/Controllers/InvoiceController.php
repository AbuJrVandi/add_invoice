<?php

namespace App\Http\Controllers;

use App\Http\Requests\InvoiceFilterRequest;
use App\Http\Requests\StoreInvoiceRequest;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoicePdfSetting;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

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
        $this->scopeInvoicesForUser($query, $request->user());

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

        $invoice = DB::transaction(function () use ($validated, $request) {
            $computed = $this->computeInvoiceTotals($validated);
            $computedItems = $computed['items'];
            $subtotal = $computed['subtotal'];
            $tax = $computed['tax'];
            $total = $computed['total'];

            $requestedNumber = trim((string) ($validated['invoice_number'] ?? ''));
            $invoice = null;
            $attempt = 0;

            while ($attempt < 6) {
                $attempt++;

                $invoiceNumber = $attempt === 1 && $requestedNumber !== ''
                    ? $requestedNumber
                    : $this->buildUniqueInvoiceNumber();

                try {
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
                        'created_by_user_id' => $request->user()?->id,
                        'status' => Invoice::STATUS_PENDING,
                        'amount_paid' => 0,
                        'balance_remaining' => $total,
                    ]);
                    break;
                } catch (QueryException $exception) {
                    if (! $this->isInvoiceNumberUniqueViolation($exception) || $attempt >= 6) {
                        throw $exception;
                    }
                }
            }

            if (! $invoice) {
                throw ValidationException::withMessages([
                    'invoice_number' => ['Unable to reserve a unique invoice number. Please retry.'],
                ]);
            }

            $invoice->items()->createMany($computedItems);

            $invoiceForPdf = $invoice->fresh('items');
            $pdfOutput = $this->renderInvoicePdfOutput($invoiceForPdf);

            $filename = 'invoice-'.$invoice->invoice_number.'-'.time().'.pdf';
            $relativePath = 'invoices/'.$filename;

            try {
                Storage::disk('public')->put($relativePath, $pdfOutput);
                $invoice->update(['pdf_path' => 'storage/'.$relativePath]);
            } catch (Throwable $exception) {
                report($exception);
                $invoice->update(['pdf_path' => null]);
            }

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

        $filename = 'invoice-preview-'.$invoiceNumber.'.pdf';
        $pdfOutput = $this->renderInvoicePdfOutput($previewInvoice);

        return response($pdfOutput, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'Cache-Control' => 'private, max-age=0, must-revalidate',
        ]);
    }

    public function pdf(Request $request, Invoice $invoice): BinaryFileResponse|Response
    {
        if (! $this->userCanAccessInvoice($request->user(), $invoice)) {
            return response()->json(['message' => 'You can only access your own invoices.'], 403);
        }

        if ($invoice->pdf_path) {
            $relativePath = str_starts_with($invoice->pdf_path, 'storage/')
                ? substr($invoice->pdf_path, strlen('storage/'))
                : ltrim($invoice->pdf_path, '/');

            if (Storage::disk('public')->exists($relativePath)) {
                $filename = 'invoice-'.$invoice->invoice_number.'.pdf';

                return response()->file(Storage::disk('public')->path($relativePath), [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="'.$filename.'"',
                    'Cache-Control' => 'private, max-age=0, must-revalidate',
                ]);
            }
        }

        // Fallback for deployments where local files are not persistent:
        // regenerate the PDF directly from invoice data.
        $invoiceModel = $invoice->loadMissing('items');
        $pdfOutput = $this->renderInvoicePdfOutput($invoiceModel);
        $filename = 'invoice-'.$invoice->invoice_number.'.pdf';

        return response($pdfOutput, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'Cache-Control' => 'private, max-age=0, must-revalidate',
        ]);
    }

    public function show(Request $request, Invoice $invoice): JsonResponse
    {
        if (! $this->userCanAccessInvoice($request->user(), $invoice)) {
            return response()->json(['message' => 'You can only access your own invoices.'], 403);
        }

        return response()->json($invoice->load('items'));
    }

    public function destroy(Request $request, Invoice $invoice): JsonResponse
    {
        if (! $this->userCanAccessInvoice($request->user(), $invoice)) {
            return response()->json(['message' => 'You can only manage your own invoices.'], 403);
        }

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
            'payment_instructions' => $settings?->payment_instructions ?: implode("\n", [
                'Please make payment to:',
                'Bank: UBA',
                'Account Name: Wickburn Services SL LTD',
                'Account No: 5401-1003-000922-9',
                'IBAN: 010401100300092257',
                'BIC/SWIFT CODE: UNAFSLFR',
            ]),
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
     * Keep invoices on one page by expanding paper size as needed.
     * Uses standard ISO sizes first (A4->A0), then a custom oversized page.
     */
    private function resolveInvoicePaper(Invoice $invoice): array
    {
        $items = $invoice->relationLoaded('items')
            ? $invoice->items
            : $invoice->items()->get();

        $descriptionLines = $items->sum(function (InvoiceItem $item): int {
            $description = (string) $item->description;
            $length = function_exists('mb_strlen')
                ? mb_strlen($description)
                : strlen($description);

            return max((int) ceil($length / 62), 1);
        });

        $itemCount = $items->count();

        // Conservative height estimate to avoid accidental page breaks.
        $fixedContentHeight = 760; // header, meta, totals, payment section, footer
        $rowBaseHeight = 26;
        $extraDescriptionLineHeight = 14;
        $safetyBuffer = 140;
        $estimatedHeight = $fixedContentHeight
            + ($itemCount * $rowBaseHeight)
            + ($descriptionLines * $extraDescriptionLineHeight)
            + $safetyBuffer;

        $portraitSizes = [
            ['name' => 'a4', 'width' => 595, 'height' => 842],
            ['name' => 'a3', 'width' => 842, 'height' => 1191],
            ['name' => 'a2', 'width' => 1191, 'height' => 1684],
            ['name' => 'a1', 'width' => 1684, 'height' => 2384],
            ['name' => 'a0', 'width' => 2384, 'height' => 3370],
        ];

        foreach ($portraitSizes as $paper) {
            if ($estimatedHeight <= $paper['height']) {
                return [
                    'size' => $paper['name'],
                    'orientation' => 'portrait',
                ];
            }
        }

        $largest = end($portraitSizes);
        return [
            'size' => [0, 0, $largest['width'], (int) ceil($estimatedHeight)],
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

    private function scopeInvoicesForUser($query, ?User $user): void
    {
        if (in_array(($user?->role ?? 'admin'), ['admin', 'owner'], true)) {
            $query->where('created_by_user_id', $user?->id);
        }
    }

    private function userCanAccessInvoice(?User $user, Invoice $invoice): bool
    {
        if (! $user) {
            return false;
        }

        if (($user->role ?? 'admin') === 'owner') {
            return true;
        }

        return (int) ($invoice->created_by_user_id ?? 0) === (int) $user->id;
    }

    private function isInvoiceNumberUniqueViolation(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;
        $driverCode = $exception->errorInfo[1] ?? null;
        $message = strtolower($exception->getMessage());

        if (in_array((string) $sqlState, ['23000', '23505'], true)) {
            return str_contains($message, 'invoice_number');
        }

        if ((int) $driverCode === 19) {
            return str_contains($message, 'invoice_number');
        }

        return false;
    }

    private function renderInvoicePdfOutput(Invoice $invoice): string
    {
        $invoiceForPdf = $invoice->relationLoaded('items')
            ? $invoice
            : $invoice->load('items');

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoiceForPdf,
            'company' => $this->buildCompanyData(),
        ]);

        $paper = $this->resolveInvoicePaper($invoiceForPdf);
        if (is_array($paper['size'])) {
            $pdf->setPaper($paper['size']);
        } else {
            $pdf->setPaper($paper['size'], $paper['orientation']);
        }

        return $pdf->output();
    }
}
