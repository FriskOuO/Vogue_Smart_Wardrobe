<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin.dev@vogueai.local'],
            [
                'name' => 'Admin Dev',
                'password' => 'Admin@123456',
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'demo.user@vogueai.local'],
            [
                'name' => 'Demo User',
                'password' => 'User@123456',
                'role' => 'user',
                'email_verified_at' => now(),
            ]
        );
    }
}
