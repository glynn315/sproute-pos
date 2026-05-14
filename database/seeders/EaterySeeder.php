<?php

namespace Database\Seeders;

use App\Domain\Eatery\Menu\Models\MenuItem;
use App\Domain\Eatery\Tables\Models\RestaurantTable;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class EaterySeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('email', 'store@baligya.com')->first();
        if (! $tenant) {
            $this->command?->warn('EaterySeeder: demo tenant not found, skipping.');
            return;
        }

        // Cashier user for table-service POS
        User::updateOrCreate(
            ['email' => 'cashier@baligya.com'],
            [
                'tenant_id'         => $tenant->id,
                'name'              => 'Demo Cashier',
                'password'          => 'Cashier@12345',
                'role'              => 'cashier',
                'is_active'         => true,
                'email_verified_at' => now(),
            ],
        );

        // Tables 1-6
        for ($i = 1; $i <= 6; $i++) {
            RestaurantTable::updateOrCreate(
                ['tenant_id' => $tenant->id, 'table_number' => $i],
                [
                    'label'  => "Table {$i}",
                    'seats'  => $i % 2 === 0 ? 4 : 2,
                    'status' => RestaurantTable::STATUS_AVAILABLE,
                ],
            );
        }

        // Menu items (per spec example)
        $menu = [
            ['name' => 'Balbacua',   'category' => 'Main',     'price' => 100.00],
            ['name' => 'Hotdog',     'category' => 'Snacks',   'price' => 15.00],
            ['name' => 'Coke',       'category' => 'Drinks',   'price' => 25.00],
            ['name' => 'Rice',       'category' => 'Main',     'price' => 15.00],
            ['name' => 'Lechon',     'category' => 'Main',     'price' => 180.00],
            ['name' => 'Iced Tea',   'category' => 'Drinks',   'price' => 30.00],
            ['name' => 'Banana Cue', 'category' => 'Snacks',   'price' => 20.00],
        ];

        foreach ($menu as $row) {
            MenuItem::updateOrCreate(
                ['tenant_id' => $tenant->id, 'name' => $row['name']],
                [
                    'category'     => $row['category'],
                    'price'        => $row['price'],
                    'availability' => true,
                ],
            );
        }

        $this->command?->info('Eatery demo seeded: 6 tables, 7 menu items, cashier@baligya.com / Cashier@12345');
    }
}
