<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'transaction_number',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total',
        'amount_paid',
        'change_amount',
        'payment_method',
        'status',
        'suspended_at',
        'suspension_note',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'subtotal'        => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount'      => 'decimal:2',
            'total'           => 'decimal:2',
            'amount_paid'     => 'decimal:2',
            'change_amount'   => 'decimal:2',
            'suspended_at'    => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SalePayment::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(SaleRefund::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isVoided(): bool
    {
        return $this->status === 'voided';
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function canBeVoided(): bool
    {
        return in_array($this->status, ['completed', 'partially_refunded'], true);
    }

    public function canBeRefunded(): bool
    {
        return in_array($this->status, ['completed', 'partially_refunded'], true);
    }

    public function canBeCommitted(): bool
    {
        return $this->status === 'draft';
    }

    public function totalRefunded(): float
    {
        return (float) $this->refunds->sum('total_refunded');
    }

    public function grossProfit(): float
    {
        return $this->items->sum(fn ($item) => ($item->unit_price - $item->product->cost_price) * $item->quantity);
    }
}
