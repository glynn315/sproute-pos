<?php

namespace App\Domain\Eatery\Tables\Models;

use App\Domain\Eatery\Orders\Models\Order;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class RestaurantTable extends Model
{
    use SoftDeletes;

    protected $table = 'restaurant_tables';

    public const STATUS_AVAILABLE    = 'available';
    public const STATUS_OCCUPIED     = 'occupied';
    public const STATUS_NOT_YET_PAID = 'not_yet_paid';

    protected $primaryKey = 'restaurant_table_id';

    protected $fillable = [
        'tenant_id',
        'table_number',
        'label',
        'seats',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'table_number' => 'integer',
            'seats'        => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'restaurant_table_id', 'restaurant_table_id');
    }

    public function activeOrder(): HasOne
    {
        return $this->hasOne(Order::class, 'restaurant_table_id', 'restaurant_table_id')
            ->where('payment_status', Order::PAYMENT_NOT_YET_PAID)
            ->latest('order_id');
    }

    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_AVAILABLE;
    }

    public function isOccupied(): bool
    {
        return in_array($this->status, [self::STATUS_OCCUPIED, self::STATUS_NOT_YET_PAID], true);
    }
}
