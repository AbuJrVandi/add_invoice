<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table): void {
            $table->dropForeign(['invoice_id']);
            $table->dropForeign(['payment_id']);

            $table->foreign('invoice_id')
                ->references('id')
                ->on('invoices')
                ->restrictOnDelete();

            $table->foreign('payment_id')
                ->references('id')
                ->on('payments')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table): void {
            $table->dropForeign(['invoice_id']);
            $table->dropForeign(['payment_id']);

            $table->foreign('invoice_id')
                ->references('id')
                ->on('invoices')
                ->cascadeOnDelete();

            $table->foreign('payment_id')
                ->references('id')
                ->on('payments')
                ->cascadeOnDelete();
        });
    }
};
