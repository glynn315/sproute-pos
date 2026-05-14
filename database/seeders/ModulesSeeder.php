<?php

namespace Database\Seeders;

use App\Domain\Modules\Models\Module;
use Illuminate\Database\Seeder;

class ModulesSeeder extends Seeder
{
    public function run(): void
    {
        $seed = [
            [
                'name'         => 'pos',
                'display_name' => 'Retail POS',
                'description'  => 'Products, categories, sales, inventory, expenses.',
                'icon'         => 'cube-outline',
                'is_active'    => true,
                'sort_order'   => 10,
            ],
            [
                'name'         => 'eatery',
                'display_name' => 'Eatery',
                'description'  => 'Restaurant tables, menu, orders, cashier payments.',
                'icon'         => 'restaurant-outline',
                'is_active'    => true,
                'sort_order'   => 20,
            ],
        ];

        foreach ($seed as $row) {
            Module::updateOrCreate(['name' => $row['name']], $row);
        }

        $this->command?->info('Modules registered: ' . implode(', ', array_column($seed, 'name')));
    }
}
