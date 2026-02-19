<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Sale;
use Illuminate\Database\Seeder;

class OwnerAnalyticsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create some overdue invoices (Risk)
        Invoice::factory()
            ->count(5)
            ->overdue()
            ->create();

        // 2. Create some pending invoices
        Invoice::factory()
            ->count(8)
            ->create(['status' => 'pending']);

        // 3. Create completed invoices with payments and sales (Performance & Liquidity)
        // We need to create these manually to ensure relationships are correct
        $completedInvoices = Invoice::factory()
            ->count(20)
            ->paid()
            ->create();

        foreach ($completedInvoices as $invoice) {
            // Create payment for this invoice
            $payment = Payment::factory()->create([
                'invoice_id' => $invoice->id,
                'amount_paid' => $invoice->total,
                'paid_at' => $invoice->paid_at,
            ]);

            // Create sale record for this transaction
            $costPrice = $invoice->subtotal * 0.6; // 60% cost
            $profit = $invoice->subtotal - $costPrice;

            Sale::factory()->create([
                'invoice_id' => $invoice->id,
                'payment_id' => $payment->id,
                'total_cost_price' => $costPrice,
                'total_sale_price' => $invoice->subtotal, // Sale price usually excludes tax in some contexts, but let's use subtotal for simplicity or total if needed. 
                // However, based on the controller: $earnedRevenue = Sale::query()->sum('total_sale_price');
                // And usually revenue is ex-tax. Let's assume subtotal is the revenue.
                'profit' => $profit,
                'created_at' => $payment->paid_at, // Align sale date with payment date
                'updated_at' => $payment->paid_at,
            ]);
        }
    }
}
