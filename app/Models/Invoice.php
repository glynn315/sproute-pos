<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    public const STATUS_PENDING   = 'pending';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_PAID      = 'paid';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_EXPIRED   = 'expired';

    protected $fillable = [
        'tenant_id',
        'subscription_plan_id',
        'invoice_number',
        'amount',
        'billing_cycle',
        'status',
        'payment_method',
        'gcash_number',
        'reference_number',
        'due_at',
        'paid_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount'  => 'decimal:2',
            'due_at'  => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function isPayable(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_SUBMITTED], true);
    }
}
