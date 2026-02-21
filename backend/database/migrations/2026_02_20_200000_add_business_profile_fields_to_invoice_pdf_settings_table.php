<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_pdf_settings', function (Blueprint $table): void {
            $table->text('company_address')->nullable()->after('company_name');
            $table->string('company_phone', 255)->nullable()->after('company_address');
            $table->string('company_email', 255)->nullable()->after('company_phone');
            $table->string('company_website', 255)->nullable()->after('company_email');
            $table->string('tax_id', 100)->nullable()->after('company_website');
            $table->string('currency_code', 10)->default('SLL')->after('tax_id');
            $table->string('contact_person', 255)->nullable()->after('issuer_name');
            $table->text('terms_conditions')->nullable()->after('payment_instructions');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_pdf_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'company_address',
                'company_phone',
                'company_email',
                'company_website',
                'tax_id',
                'currency_code',
                'contact_person',
                'terms_conditions',
            ]);
        });
    }
};
