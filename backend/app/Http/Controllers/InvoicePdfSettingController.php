<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateInvoicePdfSettingRequest;
use App\Models\InvoicePdfSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class InvoicePdfSettingController extends Controller
{
    public function show(): JsonResponse
    {
        $settings = InvoicePdfSetting::query()->first();

        return response()->json($this->buildPayload($settings));
    }

    public function update(UpdateInvoicePdfSettingRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $settings = InvoicePdfSetting::query()->firstOrCreate([], [
            'company_name' => 'CIRQON Electronics',
            'company_address' => "No. 4 Light-Foot Boston Street\nVia Radwon Street, Freetown",
            'company_phone' => '+232 74 141141 | +232 79 576950',
            'company_email' => 'info@cirqon.example',
            'company_website' => 'www.cirqon.example',
            'tax_id' => 'N/A',
            'currency_code' => 'SLL',
            'contact_person' => 'Accounts Team',
            'issuer_name' => 'Administrator',
            'payment_instructions' => $this->defaultPaymentInstructions(),
            'terms_conditions' => $this->defaultTermsConditions(),
        ]);

        $settings->company_name = $validated['company_name'];
        $settings->company_address = trim((string) ($validated['company_address'] ?? ''));
        $settings->company_phone = trim((string) ($validated['company_phone'] ?? ''));
        $settings->company_email = trim((string) ($validated['company_email'] ?? ''));
        $settings->company_website = trim((string) ($validated['company_website'] ?? ''));
        $settings->tax_id = trim((string) ($validated['tax_id'] ?? ''));
        $settings->currency_code = strtoupper(trim((string) ($validated['currency_code'] ?? 'SLL'))) ?: 'SLL';
        $settings->contact_person = trim((string) ($validated['contact_person'] ?? ''));
        $settings->issuer_name = $validated['issuer_name'];
        $settings->payment_instructions = trim((string) ($validated['payment_instructions'] ?? '')) ?: $this->defaultPaymentInstructions();
        $settings->terms_conditions = trim((string) ($validated['terms_conditions'] ?? '')) ?: $this->defaultTermsConditions();

        if ($request->hasFile('logo')) {
            if ($settings->logo_path) {
                Storage::disk('public')->delete($settings->logo_path);
            }
            $settings->logo_path = $request->file('logo')->store('invoice-settings/logos', 'public');
        }

        if ($request->hasFile('signature')) {
            if ($settings->signature_path) {
                Storage::disk('public')->delete($settings->signature_path);
            }
            $settings->signature_path = $request->file('signature')->store('invoice-settings/signatures', 'public');
        }

        if ($request->hasFile('stamp')) {
            if ($settings->stamp_path) {
                Storage::disk('public')->delete($settings->stamp_path);
            }
            $settings->stamp_path = $request->file('stamp')->store('invoice-settings/stamps', 'public');
        }

        $settings->save();

        return response()->json([
            'message' => 'PDF settings updated successfully.',
            'data' => $this->buildPayload($settings),
        ]);
    }

    private function buildPayload(?InvoicePdfSetting $settings): array
    {
        if (! $settings) {
            return [
                'company_name' => 'CIRQON Electronics',
                'company_address' => "No. 4 Light-Foot Boston Street\nVia Radwon Street, Freetown",
                'company_phone' => '+232 74 141141 | +232 79 576950',
                'company_email' => 'info@cirqon.example',
                'company_website' => 'www.cirqon.example',
                'tax_id' => 'N/A',
                'currency_code' => 'SLL',
                'contact_person' => 'Accounts Team',
                'issuer_name' => 'Administrator',
                'payment_instructions' => $this->defaultPaymentInstructions(),
                'terms_conditions' => $this->defaultTermsConditions(),
                'logo_path' => null,
                'signature_path' => null,
                'stamp_path' => null,
                'logo_url' => null,
                'signature_url' => null,
                'stamp_url' => null,
            ];
        }

        return [
            'company_name' => $settings->company_name,
            'company_address' => $settings->company_address,
            'company_phone' => $settings->company_phone,
            'company_email' => $settings->company_email,
            'company_website' => $settings->company_website,
            'tax_id' => $settings->tax_id,
            'currency_code' => $settings->currency_code ?: 'SLL',
            'contact_person' => $settings->contact_person,
            'issuer_name' => $settings->issuer_name,
            'payment_instructions' => $settings->payment_instructions ?: $this->defaultPaymentInstructions(),
            'terms_conditions' => $settings->terms_conditions ?: $this->defaultTermsConditions(),
            'logo_path' => $settings->logo_path,
            'signature_path' => $settings->signature_path,
            'stamp_path' => $settings->stamp_path,
            'logo_url' => $settings->logo_path ? Storage::disk('public')->url($settings->logo_path) : null,
            'signature_url' => $settings->signature_path ? Storage::disk('public')->url($settings->signature_path) : null,
            'stamp_url' => $settings->stamp_path ? Storage::disk('public')->url($settings->stamp_path) : null,
        ];
    }

    private function defaultPaymentInstructions(): string
    {
        return implode("\n", [
            'Please make payment to:',
            'Bank: EcoBank',
            'Account Name: CirQon Limited Services SL LTD',
            'Account No: 5401-1003-000922-9',
            'IBAN: 010401100300092257',
            'BIC/SWIFT CODE: UNAFSLFR',
        ]);
    }

    private function defaultTermsConditions(): string
    {
        return implode("\n", [
            'Payment is due within 15 days from invoice date.',
            'Goods once delivered are not returnable.',
            'A late fee may apply on overdue balances.',
            'This invoice was generated electronically and is valid without a physical stamp.',
        ]);
    }
}
