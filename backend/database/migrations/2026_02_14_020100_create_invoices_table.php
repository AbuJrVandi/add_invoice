<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->string('invoice_number', 50)->unique();
            $table->string('customer_name');
            $table->string('organization');
            $table->text('bill_to');
            $table->text('ship_to');
            $table->date('invoice_date');
            $table->date('due_date');
            $table->string('po_number', 100)->nullable();
            $table->decimal('subtotal', 12, 2);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->string('pdf_path')->nullable();
            $table->timestamps();

            $table->index('customer_name');
            $table->index('organization');
            $table->index('invoice_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
