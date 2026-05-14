<?php

namespace App\Domain\Eatery\Payments\Models;

use App\Domain\Eatery\Orders\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $table = 'payments';

    protected $primaryKey = 'payment_id';

    protected $fillable = [
        'tenant_id',
        'order_id',
        'user_id',
        'total_amount',
        'cash_received',
        'change_amount',
        'payment_method',
        'payment_date',
        'reference',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'total_amount'  => 'decimal:2',
            'cash_received' => 'decimal:2',
            'change_amount' => 'decimal:2',
            'payment_date'  => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
