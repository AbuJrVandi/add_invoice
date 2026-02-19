<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminDataIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_only_their_own_invoices_and_cannot_open_others(): void
    {
        $adminA = User::factory()->create(['role' => 'admin']);
        $adminB = User::factory()->create(['role' => 'admin']);

        $invoiceA = Invoice::factory()->create([
            'created_by_user_id' => $adminA->id,
        ]);

        $invoiceB = Invoice::factory()->create([
            'created_by_user_id' => $adminB->id,
        ]);

        Sanctum::actingAs($adminA);

        $listResponse = $this->getJson('/api/invoices');
        $listResponse->assertOk();
        $this->assertCount(1, $listResponse->json('data'));
        $this->assertSame($invoiceA->id, $listResponse->json('data.0.id'));

        $this->getJson('/api/invoices/'.$invoiceA->id)->assertOk();
        $this->getJson('/api/invoices/'.$invoiceB->id)->assertForbidden();
    }

    public function test_admin_cannot_record_or_read_payments_for_other_staff_invoices(): void
    {
        $adminA = User::factory()->create(['role' => 'admin']);
        $adminB = User::factory()->create(['role' => 'admin']);

        $invoiceA = Invoice::factory()->create([
            'created_by_user_id' => $adminA->id,
            'total' => 120,
            'amount_paid' => 0,
            'balance_remaining' => 120,
            'status' => Invoice::STATUS_PENDING,
        ]);

        $invoiceB = Invoice::factory()->create([
            'created_by_user_id' => $adminB->id,
            'total' => 220,
            'amount_paid' => 0,
            'balance_remaining' => 220,
            'status' => Invoice::STATUS_PENDING,
        ]);

        $paymentA = Payment::factory()->create([
            'invoice_id' => $invoiceA->id,
            'amount_paid' => 40,
            'created_by' => $adminA->id,
        ]);

        Payment::factory()->create([
            'invoice_id' => $invoiceB->id,
            'amount_paid' => 60,
            'created_by' => $adminB->id,
        ]);

        Sanctum::actingAs($adminA);

        $paymentsResponse = $this->getJson('/api/payments');
        $paymentsResponse->assertOk();
        $this->assertCount(1, $paymentsResponse->json('data'));
        $this->assertSame($paymentA->id, $paymentsResponse->json('data.0.id'));

        $invoiceSearchResponse = $this->getJson('/api/payments/search-invoices');
        $invoiceSearchResponse->assertOk();
        $this->assertCount(1, $invoiceSearchResponse->json('data'));
        $this->assertSame($invoiceA->id, $invoiceSearchResponse->json('data.0.id'));

        $this->getJson('/api/payments/'.$paymentA->id)->assertOk();
        $this->postJson('/api/payments', [
            'invoice_id' => $invoiceB->id,
            'amount_paid' => 50,
            'payment_method' => 'cash',
        ])->assertForbidden();
    }

    public function test_dashboard_metrics_are_scoped_to_staff_own_records(): void
    {
        $adminA = User::factory()->create(['role' => 'admin']);
        $adminB = User::factory()->create(['role' => 'admin']);

        $invoiceA = Invoice::factory()->create([
            'created_by_user_id' => $adminA->id,
            'status' => Invoice::STATUS_DUE,
            'total' => 100,
            'amount_paid' => 30,
            'balance_remaining' => 70,
        ]);

        $invoiceB = Invoice::factory()->create([
            'created_by_user_id' => $adminB->id,
            'status' => Invoice::STATUS_DUE,
            'total' => 200,
            'amount_paid' => 80,
            'balance_remaining' => 120,
        ]);

        $paymentA = Payment::factory()->create([
            'invoice_id' => $invoiceA->id,
            'amount_paid' => 30,
            'created_by' => $adminA->id,
            'paid_at' => now(),
        ]);

        $paymentB = Payment::factory()->create([
            'invoice_id' => $invoiceB->id,
            'amount_paid' => 80,
            'created_by' => $adminB->id,
            'paid_at' => now(),
        ]);

        Sale::factory()->create([
            'invoice_id' => $invoiceA->id,
            'payment_id' => $paymentA->id,
            'total_sale_price' => 100,
            'profit' => 100,
        ]);

        Sale::factory()->create([
            'invoice_id' => $invoiceB->id,
            'payment_id' => $paymentB->id,
            'total_sale_price' => 200,
            'profit' => 200,
        ]);

        Sanctum::actingAs($adminA);

        $response = $this->getJson('/api/dashboard');
        $response->assertOk();
        $this->assertSame(1, (int) $response->json('total_invoices'));
        $this->assertSame(1, (int) $response->json('total_sales'));
        $this->assertSame(70.0, (float) $response->json('outstanding_amount'));
        $this->assertSame(30.0, (float) $response->json('cash_collected'));
        $this->assertSame(100.0, (float) $response->json('earned_revenue'));
    }
}
