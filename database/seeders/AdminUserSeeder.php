<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@baligya.com'],
            [
                'name'              => 'Baligya Admin',
                'email'             => 'admin@baligya.com',
                'password'          => 'Admin@12345',
                'role'              => 'super_admin',
                'is_active'         => true,
                'email_verified_at' => now(),
                'tenant_id'         => null,
            ]
        );

        $this->command->info('Super admin created: admin@baligya.com / Admin@12345');
    }
}
