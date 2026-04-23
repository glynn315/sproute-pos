<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tenant extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'logo_url',
        'primary_color',
        'secondary_color',
        'status',
        'subscription_plan_id',
        'subscription_ends_at',
    ];

    protected function casts(): array
    {
        return [
            'subscription_ends_at' => 'datetime',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function owner(): HasOne
    {
        return $this->hasOne(User::class)->where('role', 'owner');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(User::class)->whereIn('role', ['manager', 'cashier']);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function inventoryLogs(): HasMany
    {
        return $this->hasMany(InventoryLog::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(TenantVerification::class);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function isVerified(): bool
    {
        return $this->status === 'verified';
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['verified']);
    }

    public function hasActiveSubscription(): bool
    {
        return $this->subscription_plan_id !== null
            && ($this->subscription_ends_at === null || $this->subscription_ends_at->isFuture());
    }

    public function employeeCount(): int
    {
        return $this->employees()->count();
    }

    public function maxEmployees(): int
    {
        return $this->subscriptionPlan?->max_employees ?? 3;
    }

    public function canAddEmployee(): bool
    {
        return $this->employeeCount() < $this->maxEmployees();
    }
}
