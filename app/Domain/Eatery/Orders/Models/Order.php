<?php

namespace App\Domain\Eatery\Orders\Models;

use App\Domain\Eatery\Payments\Models\Payment;
use App\Domain\Eatery\Tables\Models\RestaurantTable;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    protected $table = 'orders';

    public const PAYMENT_NOT_YET_PAID = 'not_yet_paid';
    public const PAYMENT_PAID         = 'paid';
    public const PAYMENT_CANCELLED    = 'cancelled';

    protected $primaryKey = 'order_id';

    protected $fillable = [
        'tenant_id',
        'restaurant_table_id',
        'user_id',
        'order_number',
        'subtotal',
        'total_amount',
        'payment_status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'subtotal'     => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(RestaurantTable::class, 'restaurant_table_id', 'restaurant_table_id');
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'order_id');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class, 'order_id', 'order_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'order_id', 'order_id');
    }

    public function isPaid(): bool
    {
        return $this->payment_status === self::PAYMENT_PAID;
    }

    public function isUnpaid(): bool
    {
        return $this->payment_status === self::PAYMENT_NOT_YET_PAID;
    }
}
