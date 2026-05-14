<?php

namespace App\Domain\Eatery\Menu\Services;

use App\Domain\Eatery\Menu\DTOs\StoreMenuItemDTO;
use App\Domain\Eatery\Menu\DTOs\UpdateMenuItemDTO;
use App\Domain\Eatery\Menu\Models\MenuItem;
use App\Domain\Eatery\Menu\Repositories\MenuItemRepository;
use App\Traits\AuditLogger;

class MenuItemService
{
    use AuditLogger;

    public function __construct(private readonly MenuItemRepository $repo) {}

    public function create(StoreMenuItemDTO $dto): MenuItem
    {
        $item = $this->repo->create($dto->toArray());
        $this->audit('created', 'MenuItem', $item->menu_item_id, null, $item->toArray(), $dto->tenantId);
        return $item;
    }

    public function update(MenuItem $item, UpdateMenuItemDTO $dto): MenuItem
    {
        $old   = $item->toArray();
        $patch = $dto->toArray();
        if (! empty($patch)) {
            $item->fill($patch)->save();
        }
        $this->audit('updated', 'MenuItem', $item->menu_item_id, $old, $item->fresh()->toArray(), $item->tenant_id);
        return $item->fresh();
    }

    public function destroy(MenuItem $item): void
    {
        $old = $item->toArray();
        $item->delete(); // soft-delete preserves historical orders
        $this->audit('deleted', 'MenuItem', $old['menu_item_id'] ?? null, $old, null, $item->tenant_id);
    }
}
