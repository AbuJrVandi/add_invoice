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
            'company_name' => 'East Repair Inc.',
            'issuer_name' => 'Administrator',
        ]);

        $settings->company_name = $validated['company_name'];
        $settings->issuer_name = $validated['issuer_name'];

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
                'company_name' => 'East Repair Inc.',
                'issuer_name' => 'Administrator',
                'logo_path' => null,
                'signature_path' => null,
                'logo_url' => null,
                'signature_url' => null,
            ];
        }

        return [
            'company_name' => $settings->company_name,
            'issuer_name' => $settings->issuer_name,
            'logo_path' => $settings->logo_path,
            'signature_path' => $settings->signature_path,
            'logo_url' => $settings->logo_path ? Storage::disk('public')->url($settings->logo_path) : null,
            'signature_url' => $settings->signature_path ? Storage::disk('public')->url($settings->signature_path) : null,
        ];
    }
}
