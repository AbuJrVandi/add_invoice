<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OwnerAdminActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_only_managed_admin_activity(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $otherOwner = User::factory()->create(['role' => 'owner']);

        $managedAdmin = User::factory()->create([
            'role' => 'admin',
            'managed_by_owner_id' => $owner->id,
        ]);

        $otherAdmin = User::factory()->create([
            'role' => 'admin',
            'managed_by_owner_id' => $otherOwner->id,
        ]);

        $managedInvoice = Invoice::factory()->create([
            'created_by_user_id' => $managedAdmin->id,
        ]);

        $otherInvoice = Invoice::factory()->create([
            'created_by_user_id' => $otherAdmin->id,
        ]);

        $managedPayment = Payment::factory()->create([
            'invoice_id' => $managedInvoice->id,
            'created_by' => $managedAdmin->id,
        ]);

        Payment::factory()->create([
            'invoice_id' => $otherInvoice->id,
            'created_by' => $otherAdmin->id,
        ]);

        Sanctum::actingAs($owner);

        $listResponse = $this->getJson('/api/owner/admin-activity');
        $listResponse->assertOk();
        $this->assertCount(1, $listResponse->json('data'));
        $this->assertSame($managedAdmin->id, $listResponse->json('data.0.id'));
        $this->assertSame(1, (int) $listResponse->json('data.0.metrics.total_invoices'));
        $this->assertSame(1, (int) $listResponse->json('data.0.metrics.total_receipts'));

        $detailResponse = $this->getJson('/api/owner/admin-activity/'.$managedAdmin->id);
        $detailResponse->assertOk();
        $this->assertSame($managedInvoice->id, $detailResponse->json('invoices.data.0.id'));
        $this->assertSame($managedPayment->id, $detailResponse->json('receipts.data.0.id'));

        $this->getJson('/api/owner/admin-activity/'.$otherAdmin->id)->assertNotFound();
    }

    public function test_owner_can_delete_managed_admin_and_purge_related_data(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);

        $managedAdmin = User::factory()->create([
            'role' => 'admin',
            'managed_by_owner_id' => $owner->id,
        ]);

        $invoice = Invoice::factory()->create([
            'created_by_user_id' => $managedAdmin->id,
        ]);

        $payment = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'created_by' => $managedAdmin->id,
        ]);

        Sale::query()->create([
            'invoice_id' => $invoice->id,
            'payment_id' => $payment->id,
            'total_cost_price' => 0,
            'total_sale_price' => 100,
            'profit' => 100,
        ]);

        Sanctum::actingAs($owner);

        $response = $this->deleteJson('/api/owner/admin-credentials/'.$managedAdmin->id);
        $response->assertOk();
        $this->assertSame(1, (int) $response->json('data.deleted_invoices'));
        $this->assertSame(1, (int) $response->json('data.deleted_receipts'));
        $this->assertSame(1, (int) $response->json('data.deleted_sales'));

        $this->assertDatabaseMissing('users', ['id' => $managedAdmin->id]);
        $this->assertDatabaseMissing('invoices', ['id' => $invoice->id]);
        $this->assertDatabaseMissing('payments', ['id' => $payment->id]);
        $this->assertDatabaseMissing('sales', ['invoice_id' => $invoice->id]);
    }
}

