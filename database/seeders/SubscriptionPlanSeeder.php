<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name'          => 'basic',
                'display_name'  => 'Basic',
                'price'         => 299.00,
                'billing_cycle' => 'monthly',
                'max_employees' => 3,
                'max_products'  => 100,
                'features'      => [
                    'pos_terminal'    => true,
                    'inventory'       => true,
                    'reports'         => false,
                    'multi_employee'  => true,
                    'expense_tracking'=> false,
                    'custom_branding' => false,
                ],
                'is_active' => true,
            ],
            [
                'name'          => 'standard',
                'display_name'  => 'Standard',
                'price'         => 599.00,
                'billing_cycle' => 'monthly',
                'max_employees' => 3,
                'max_products'  => 500,
                'features'      => [
                    'pos_terminal'    => true,
                    'inventory'       => true,
                    'reports'         => true,
                    'multi_employee'  => true,
                    'expense_tracking'=> true,
                    'custom_branding' => false,
                ],
                'is_active' => true,
            ],
            [
                'name'          => 'premium',
                'display_name'  => 'Premium',
                'price'         => 999.00,
                'billing_cycle' => 'monthly',
                'max_employees' => 3,
                'max_products'  => -1,
                'features'      => [
                    'pos_terminal'    => true,
                    'inventory'       => true,
                    'reports'         => true,
                    'multi_employee'  => true,
                    'expense_tracking'=> true,
                    'custom_branding' => true,
                ],
                'is_active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(['name' => $plan['name']], $plan);
        }
    }
}
