<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleRefund extends Model
{
    protected $fillable = [
        'sale_id',
        'refunded_by_user_id',
        'total_refunded',
        'reason',
        'refund_method',
    ];

    protected function casts(): array
    {
        return [
            'total_refunded' => 'decimal:2',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function refundedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refunded_by_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleRefundItem::class);
    }
}
