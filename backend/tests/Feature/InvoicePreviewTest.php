<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InvoicePreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_generate_invoice_pdf_preview_from_draft_payload(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        Sanctum::actingAs($admin);

        $payload = [
            'invoice_number' => 'INV-PREVIEW-0001',
            'customer_name' => 'Preview Customer',
            'organization' => 'Preview Org',
            'bill_to' => 'Preview billing address',
            'ship_to' => 'Preview shipping address',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'po_number' => 'PO-12345',
            'tax' => 10,
            'items' => [
                [
                    'description' => 'Item A',
                    'quantity' => 2,
                    'unit_price' => 50,
                ],
            ],
        ];

        $response = $this->postJson('/api/invoices/preview-pdf', $payload);

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');

        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_owner_cannot_generate_invoice_preview_pdf(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
        ]);

        Sanctum::actingAs($owner);

        $response = $this->postJson('/api/invoices/preview-pdf', [
            'customer_name' => 'X',
            'organization' => 'Y',
            'bill_to' => 'Z',
            'ship_to' => 'Z',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'items' => [
                ['description' => 'Item', 'quantity' => 1, 'unit_price' => 1],
            ],
        ]);

        $response->assertForbidden();
    }
}
