<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Eatery\Menu\DTOs\StoreMenuItemDTO;
use App\Domain\Eatery\Menu\DTOs\UpdateMenuItemDTO;
use App\Domain\Eatery\Menu\Repositories\MenuItemRepository;
use App\Domain\Eatery\Menu\Services\MenuItemService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Eatery\Menu\StoreMenuItemRequest;
use App\Http\Requests\Eatery\Menu\UpdateMenuItemRequest;
use App\Http\Resources\MenuItemResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MenuItemController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly MenuItemService    $service,
        private readonly MenuItemRepository $repo,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $filters  = $request->only(['availability', 'category', 'search']);

        if ($request->boolean('paginate', false)) {
            $perPage = min((int) $request->get('per_page', 20), 100);
            $items   = $this->repo->paginateForTenant($tenantId, $perPage, $filters);
            return $this->success(MenuItemResource::collection($items));
        }

        $items = $this->repo->listForTenant($tenantId, $filters);
        return $this->success(MenuItemResource::collection($items));
    }

    public function show(Request $request, int $menu): JsonResponse
    {
        $item = $this->repo->findForTenant($request->user()->tenant_id, $menu);
        if (! $item) return $this->notFound('Menu item not found');
        return $this->success(new MenuItemResource($item));
    }

    public function store(StoreMenuItemRequest $request): JsonResponse
    {
        $item = $this->service->create(StoreMenuItemDTO::fromRequest($request, $request->user()));
        return $this->created(new MenuItemResource($item), 'Menu item created');
    }

    public function update(UpdateMenuItemRequest $request, int $menu): JsonResponse
    {
        $item = $this->repo->findForTenant($request->user()->tenant_id, $menu);
        if (! $item) return $this->notFound('Menu item not found');

        $updated = $this->service->update($item, UpdateMenuItemDTO::fromRequest($request));
        return $this->success(new MenuItemResource($updated), 'Menu item updated');
    }

    public function destroy(Request $request, int $menu): JsonResponse
    {
        if (! $request->user()->canManage()) {
            return $this->forbidden('Only managers/owners can delete menu items.');
        }
        $item = $this->repo->findForTenant($request->user()->tenant_id, $menu);
        if (! $item) return $this->notFound('Menu item not found');

        $this->service->destroy($item);
        return $this->noContent('Menu item deleted');
    }
}
