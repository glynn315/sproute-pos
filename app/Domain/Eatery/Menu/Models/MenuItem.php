<?php

namespace App\Domain\Eatery\Menu\Models;

use App\Domain\Eatery\Orders\Models\OrderItem;
use App\Models\Category;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MenuItem extends Model
{
    use SoftDeletes;

    protected $table = 'menu_items';

    protected $primaryKey = 'menu_item_id';

    protected $fillable = [
        'tenant_id',
        'category_id',
        'name',
        'description',
        'category',
        'price',
        'image_url',
        'availability',
    ];

    protected function casts(): array
    {
        return [
            'price'        => 'decimal:2',
            'availability' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function categoryRef(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'menu_item_id', 'menu_item_id');
    }
}
