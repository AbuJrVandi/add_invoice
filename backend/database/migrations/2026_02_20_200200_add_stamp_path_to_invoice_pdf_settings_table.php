<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_pdf_settings', function (Blueprint $table): void {
            $table->string('stamp_path')->nullable()->after('signature_path');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_pdf_settings', function (Blueprint $table): void {
            $table->dropColumn('stamp_path');
        });
    }
};
