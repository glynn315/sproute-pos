<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoStoreSeeder extends Seeder
{
    public function run(): void
    {
        $plan = SubscriptionPlan::where('name', 'standard')->first();

        $tenant = Tenant::updateOrCreate(
            ['email' => 'store@baligya.com'],
            [
                'name' => 'Baligya Demo Store',
                'phone' => '09171234567',
                'address' => 'Cebu City',
                'status' => 'verified',
                'subscription_plan_id' => $plan?->id,
                'subscription_ends_at' => now()->addYear(),
            ],
        );

        User::updateOrCreate(
            ['email' => 'owner@baligya.com'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Demo Owner',
                'password' => 'Owner@12345',
                'role' => 'owner',
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        $category = Category::updateOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'General'],
            ['description' => 'Demo products'],
        );

        Product::updateOrCreate(
            ['tenant_id' => $tenant->id, 'sku' => 'DEMO-COFFEE'],
            [
                'category_id' => $category->id,
                'name' => 'Iced Coffee',
                'description' => 'Demo POS item',
                'barcode' => '100000000001',
                'price' => 89,
                'cost_price' => 45,
                'stock_quantity' => 25,
                'reorder_level' => 5,
                'is_active' => true,
            ],
        );

        Product::updateOrCreate(
            ['tenant_id' => $tenant->id, 'sku' => 'DEMO-BREAD'],
            [
                'category_id' => $category->id,
                'name' => 'Banana Bread',
                'description' => 'Demo POS item',
                'barcode' => '100000000002',
                'price' => 65,
                'cost_price' => 30,
                'stock_quantity' => 18,
                'reorder_level' => 5,
                'is_active' => true,
            ],
        );

        $this->command->info('Demo store owner created: owner@baligya.com / Owner@12345');
    }
}
