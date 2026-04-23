<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'price',
        'billing_cycle',
        'max_employees',
        'max_products',
        'features',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'features'      => 'array',
            'is_active'     => 'boolean',
            'price'         => 'decimal:2',
            'max_employees' => 'integer',
            'max_products'  => 'integer',
        ];
    }

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    public function hasUnlimitedProducts(): bool
    {
        return $this->max_products === -1;
    }
}
