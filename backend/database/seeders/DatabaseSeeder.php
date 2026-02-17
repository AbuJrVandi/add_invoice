<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $owner = User::query()->updateOrCreate([
            'email' => 'owner@invoicesystem.com',
        ], [
            'name' => 'Owner',
            'role' => 'owner',
            'password' => Hash::make('password'),
        ]);

        User::query()->updateOrCreate([
            'email' => 'admin@invoicesystem.com',
        ], [
            'name' => 'Administrator',
            'role' => 'admin',
            'password' => Hash::make('password'),
            'managed_by_owner_id' => $owner->id,
            'owner_password_ciphertext' => Crypt::encryptString('password'),
            'owner_password_changed_at' => now(),
            'owner_force_password_notice' => false,
        ]);
    }
}
