<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OwnerOperationsAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_access_invoice_and_payment_operations_endpoints(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
        ]);

        Sanctum::actingAs($owner);

        $this->getJson('/api/invoices/next-number')->assertOk();
        $this->getJson('/api/invoices')->assertOk();
        $this->getJson('/api/payments')->assertOk();
        $this->getJson('/api/payments/search-invoices')->assertOk();
    }

    public function test_pdf_settings_are_owner_only(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        Sanctum::actingAs($owner);
        $this->getJson('/api/pdf-settings')->assertOk();

        Sanctum::actingAs($admin);
        $this->getJson('/api/pdf-settings')->assertForbidden();
    }

    public function test_owner_invoice_and_payment_lists_are_scoped_to_owner_records_only(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $admin = User::factory()->create(['role' => 'admin']);

        $ownerInvoice = Invoice::factory()->create([
            'created_by_user_id' => $owner->id,
            'status' => Invoice::STATUS_PENDING,
            'total' => 120,
            'amount_paid' => 0,
            'balance_remaining' => 120,
        ]);

        $adminInvoice = Invoice::factory()->create([
            'created_by_user_id' => $admin->id,
            'status' => Invoice::STATUS_PENDING,
            'total' => 220,
            'amount_paid' => 0,
            'balance_remaining' => 220,
        ]);

        $ownerPayment = Payment::factory()->create([
            'invoice_id' => $ownerInvoice->id,
            'created_by' => $owner->id,
        ]);

        Payment::factory()->create([
            'invoice_id' => $adminInvoice->id,
            'created_by' => $admin->id,
        ]);

        Sanctum::actingAs($owner);

        $invoiceList = $this->getJson('/api/invoices');
        $invoiceList->assertOk();
        $this->assertCount(1, $invoiceList->json('data'));
        $this->assertSame($ownerInvoice->id, $invoiceList->json('data.0.id'));

        $paymentList = $this->getJson('/api/payments');
        $paymentList->assertOk();
        $this->assertCount(1, $paymentList->json('data'));
        $this->assertSame($ownerPayment->id, $paymentList->json('data.0.id'));

        $searchInvoices = $this->getJson('/api/payments/search-invoices');
        $searchInvoices->assertOk();
        $this->assertCount(1, $searchInvoices->json('data'));
        $this->assertSame($ownerInvoice->id, $searchInvoices->json('data.0.id'));

        $this->postJson('/api/payments', [
            'invoice_id' => $adminInvoice->id,
            'amount_paid' => 50,
            'payment_method' => 'cash',
        ])->assertForbidden();
    }
}
