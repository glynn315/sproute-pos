<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Eatery\Tables\DTOs\StoreRestaurantTableDTO;
use App\Domain\Eatery\Tables\DTOs\UpdateRestaurantTableDTO;
use App\Domain\Eatery\Tables\Repositories\TableRepository;
use App\Domain\Eatery\Tables\Services\TableService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Eatery\Tables\StoreRestaurantTableRequest;
use App\Http\Requests\Eatery\Tables\UpdateRestaurantTableRequest;
use App\Http\Resources\RestaurantTableResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RestaurantTableController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly TableService    $service,
        private readonly TableRepository $repo,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $filters  = $request->only(['status', 'search']);

        if ($request->boolean('paginate', false)) {
            $perPage = min((int) $request->get('per_page', 20), 100);
            $tables  = $this->repo->paginateForTenant($tenantId, $perPage, $filters);
            return $this->success(RestaurantTableResource::collection($tables));
        }

        $tables = $this->repo->listForTenant($tenantId, $filters);
        return $this->success(RestaurantTableResource::collection($tables));
    }

    public function show(Request $request, int $table): JsonResponse
    {
        $row = $this->repo->findForTenant($request->user()->tenant_id, $table);
        if (! $row) return $this->notFound('Table not found');
        return $this->success(new RestaurantTableResource($row));
    }

    public function store(StoreRestaurantTableRequest $request): JsonResponse
    {
        $table = $this->service->create(
            StoreRestaurantTableDTO::fromRequest($request, $request->user())
        );
        return $this->created(new RestaurantTableResource($table), 'Table created');
    }

    public function update(UpdateRestaurantTableRequest $request, int $table): JsonResponse
    {
        $row = $this->repo->findForTenant($request->user()->tenant_id, $table);
        if (! $row) return $this->notFound('Table not found');

        $updated = $this->service->update($row, UpdateRestaurantTableDTO::fromRequest($request));
        return $this->success(new RestaurantTableResource($updated), 'Table updated');
    }

    public function destroy(Request $request, int $table): JsonResponse
    {
        if (! $request->user()->canManage()) {
            return $this->forbidden('Only managers/owners can delete tables.');
        }
        $row = $this->repo->findForTenant($request->user()->tenant_id, $table);
        if (! $row) return $this->notFound('Table not found');

        $this->service->destroy($row);
        return $this->noContent('Table deleted');
    }

    /**
     * List tables whose status doesn't match their order state — for tracing
     * anomalies. Read-only.
     */
    public function anomalies(Request $request): JsonResponse
    {
        return $this->success($this->service->detectAnomalies($request->user()->tenant_id));
    }

    /**
     * Heal anomalies in one shot. Each fix is audited with reason='manual_sync'.
     */
    public function syncStatuses(Request $request): JsonResponse
    {
        if (! $request->user()->canManage()) {
            return $this->forbidden('Only managers/owners can sync table statuses.');
        }
        $result = $this->service->syncStatuses($request->user()->tenant_id, $request->user()->id);
        return $this->success($result, "Synced {$result['healed']} of {$result['detected']} table(s)");
    }
}
