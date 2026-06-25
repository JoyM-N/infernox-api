<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Create the default super admin account
        // This is the first account you use to log in
        // CHANGE THIS PASSWORD immediately in production
        $admin = User::firstOrCreate(
            ['email' => 'admin@infernox.com'],
            [
                'name'      => 'INFERNOX Admin',
                'password'  => 'password123',  // auto-hashed by User model cast
                'is_active' => true,
            ]
        );

        $admin->assignRole('super_admin');

        // Create a test operator account
        $operator = User::firstOrCreate(
            ['email' => 'operator@infernox.com'],
            [
                'name'      => 'Test Operator',
                'password'  => 'password123',
                'is_active' => true,
            ]
        );

        $operator->assignRole('operator');

        $this->command->info('Admin users seeded.');
        $this->command->table(
            ['Email', 'Password', 'Role'],
            [
                ['admin@infernox.com',    'password123', 'super_admin'],
                ['operator@infernox.com', 'password123', 'operator'],
            ]
        );
    }
}