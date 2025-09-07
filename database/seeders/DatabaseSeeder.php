<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create a normal test user
        if (! User::where('email', 'test@example.com')->exists()) {
            User::create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'phone' => '1111111111',
                'password' => Hash::make('password'),
                'role' => 'user',
            ]);
        }

        // Create an Admin user
        if (! User::where('email', 'admin@foodwaste.com')->exists()) {
            User::create([
                'name' => 'Super Admin',
                'email' => 'admin@example.com',
                'phone' => '0000000000',
                'password' => Hash::make('Admin@1234'),
                'role' => 'admin',
            ]);
        }
    }
}
