<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->string('status', 20)->default('pending')->after('pdf_path');
            $table->decimal('amount_paid', 12, 2)->default(0)->after('status');
            $table->decimal('balance_remaining', 12, 2)->default(0)->after('amount_paid');
            $table->timestamp('paid_at')->nullable()->after('balance_remaining');

            $table->index('status');
        });

        // Set balance_remaining = total for all existing invoices
        DB::statement('UPDATE invoices SET balance_remaining = total, status = \'pending\' WHERE balance_remaining = 0 AND amount_paid = 0');
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'amount_paid', 'balance_remaining', 'paid_at']);
        });
    }
};
