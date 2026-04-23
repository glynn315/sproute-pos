<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Inventory\DTOs\CreateInventoryDTO;
use App\Domain\Inventory\Repositories\InventoryRepository;
use App\Domain\Inventory\Services\InventoryService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\AdjustStockRequest;
use App\Http\Resources\InventoryLogResource;
use App\Http\Resources\ProductResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly InventoryService    $inventoryService,
        private readonly InventoryRepository $repo,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['product_id', 'type', 'date_from', 'date_to']);
        $perPage = min((int) $request->get('per_page', 20), 100);
        $logs    = $this->repo->logsForTenant($request->user()->tenant_id, $perPage, $filters);

        return $this->success(InventoryLogResource::collection($logs));
    }

    public function adjust(AdjustStockRequest $request): JsonResponse
    {
        $product = $this->inventoryService->adjust(
            CreateInventoryDTO::fromRequest($request),
            $request->user()->tenant_id
        );

        return $this->success(new ProductResource($product->load('category')), 'Stock adjusted');
    }
}
