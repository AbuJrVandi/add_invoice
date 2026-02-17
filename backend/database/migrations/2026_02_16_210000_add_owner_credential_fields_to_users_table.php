<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('managed_by_owner_id')
                ->nullable()
                ->after('role')
                ->constrained('users')
                ->nullOnDelete();
            $table->text('owner_password_ciphertext')->nullable()->after('password');
            $table->timestamp('owner_password_changed_at')->nullable()->after('owner_password_ciphertext');
            $table->boolean('owner_force_password_notice')->default(false)->after('owner_password_changed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('managed_by_owner_id');
            $table->dropColumn([
                'owner_password_ciphertext',
                'owner_password_changed_at',
                'owner_force_password_notice',
            ]);
        });
    }
};

