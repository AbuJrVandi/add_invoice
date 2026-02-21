<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OwnerAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_live_analytics_payload(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
        ]);
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $invoice = Invoice::query()->create([
            'invoice_number' => 'INV-TEST-0001',
            'customer_name' => 'Acme Corp',
            'organization' => 'Acme Corp',
            'bill_to' => 'Acme billing address',
            'ship_to' => 'Acme shipping address',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(10)->toDateString(),
            'subtotal' => 100,
            'tax' => 0,
            'total' => 100,
            'status' => Invoice::STATUS_DUE,
            'amount_paid' => 40,
            'balance_remaining' => 60,
            'created_by_user_id' => $admin->id,
        ]);

        $payment = Payment::query()->create([
            'invoice_id' => $invoice->id,
            'receipt_number' => 'RCP-TEST-0001',
            'amount_paid' => 40,
            'payment_method' => 'cash',
            'paid_at' => now(),
        ]);

        Sale::query()->create([
            'invoice_id' => $invoice->id,
            'payment_id' => $payment->id,
            'total_cost_price' => 0,
            'total_sale_price' => 100,
            'profit' => 100,
        ]);

        Sanctum::actingAs($owner);

        $response = $this->getJson('/api/owner/analytics');

        $response->assertOk()
            ->assertJsonStructure([
                'generated_at',
                'overview' => [
                    'total_invoices',
                    'total_receipts',
                    'invoice_completion_rate',
                ],
                'liquidity',
                'performance',
                'risk',
                'trends',
                'series',
                'weekly_activity' => ['labels', 'invoices_created', 'receipts_issued', 'collected_cash'],
                'distributions' => ['invoice_status', 'payment_methods'],
                'team_activity' => ['admins', 'unassigned_invoices'],
                'recent' => ['invoices', 'receipts'],
            ]);

        $this->assertNotEmpty($response->json('recent.invoices'));
        $this->assertNotEmpty($response->json('recent.receipts'));
        $this->assertSame($admin->id, $response->json('recent.invoices.0.created_by_user_id'));
        $this->assertSame($admin->name, $response->json('recent.invoices.0.creator.name'));
        $this->assertSame(1, (int) $response->json('team_activity.admins.0.total_invoices'));
    }

    public function test_non_owner_cannot_access_owner_analytics(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        Sanctum::actingAs($admin);

        $this->getJson('/api/owner/analytics')->assertForbidden();
    }
}
