<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('invoices', 'created_by_user_id')) {
            Schema::table('invoices', function (Blueprint $table): void {
                $table->foreignId('created_by_user_id')
                    ->nullable()
                    ->after('pdf_path')
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }

        // Best-effort backfill for existing invoices using payment creator history.
        DB::statement('
            UPDATE invoices
            SET created_by_user_id = (
                SELECT p.created_by
                FROM payments p
                WHERE p.invoice_id = invoices.id
                AND p.created_by IS NOT NULL
                AND EXISTS (SELECT 1 FROM users u WHERE u.id = p.created_by)
                ORDER BY p.id ASC
                LIMIT 1
            )
            WHERE created_by_user_id IS NULL
            AND EXISTS (
                SELECT 1
                FROM payments p2
                WHERE p2.invoice_id = invoices.id
                AND p2.created_by IS NOT NULL
                AND EXISTS (SELECT 1 FROM users u2 WHERE u2.id = p2.created_by)
            )
        ');

        // If there is only one admin account, assign remaining legacy invoices to that staff account.
        $adminIds = DB::table('users')
            ->where('role', 'admin')
            ->pluck('id');

        if ($adminIds->count() === 1) {
            DB::table('invoices')
                ->whereNull('created_by_user_id')
                ->update(['created_by_user_id' => $adminIds->first()]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('invoices', 'created_by_user_id')) {
            return;
        }

        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('created_by_user_id');
        });
    }
};
