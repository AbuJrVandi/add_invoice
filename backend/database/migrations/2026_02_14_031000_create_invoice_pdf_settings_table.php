<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_pdf_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('company_name')->default('East Repair Inc.');
            $table->string('issuer_name')->default('Administrator');
            $table->string('logo_path')->nullable();
            $table->string('signature_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_pdf_settings');
    }
};
