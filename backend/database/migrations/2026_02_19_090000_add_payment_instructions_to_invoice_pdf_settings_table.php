<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_pdf_settings', function (Blueprint $table): void {
            $table->text('payment_instructions')->nullable()->after('issuer_name');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_pdf_settings', function (Blueprint $table): void {
            $table->dropColumn('payment_instructions');
        });
    }
};
