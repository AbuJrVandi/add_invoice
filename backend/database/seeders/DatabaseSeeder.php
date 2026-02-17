<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $ownerEmail = (string) env('OWNER_EMAIL', 'owner@invoicesystem.com');
        $ownerName = (string) env('OWNER_NAME', 'Owner');
        $ownerPassword = (string) env('OWNER_PASSWORD', 'password');

        User::query()->updateOrCreate([
            'email' => $ownerEmail,
        ], [
            'name' => $ownerName,
            'role' => 'owner',
            'password' => Hash::make($ownerPassword),
        ]);
    }
}
