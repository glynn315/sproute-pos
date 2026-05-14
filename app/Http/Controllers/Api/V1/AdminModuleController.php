<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Modules\DTOs\StoreModuleDTO;
use App\Domain\Modules\DTOs\UpdateModuleDTO;
use App\Domain\Modules\Repositories\ModuleRepository;
use App\Domain\Modules\Services\ModuleService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Modules\StoreModuleRequest;
use App\Http\Requests\Admin\Modules\UpdateModuleRequest;
use App\Http\Resources\ModuleResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminModuleController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ModuleService    $service,
        private readonly ModuleRepository $repo,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $activeOnly = $request->boolean('active_only', false);
        $modules    = $this->repo->all($activeOnly);
        return $this->success(ModuleResource::collection($modules));
    }

    public function show(int $module): JsonResponse
    {
        $m = $this->repo->find($module);
        if (! $m) return $this->notFound('Module not found');
        return $this->success(new ModuleResource($m));
    }

    public function store(StoreModuleRequest $request): JsonResponse
    {
        $m = $this->service->create(StoreModuleDTO::fromRequest($request));
        return $this->created(new ModuleResource($m), 'Module registered');
    }

    public function update(UpdateModuleRequest $request, int $module): JsonResponse
    {
        $m = $this->repo->find($module);
        if (! $m) return $this->notFound('Module not found');

        $updated = $this->service->update($m, UpdateModuleDTO::fromRequest($request));
        return $this->success(new ModuleResource($updated), 'Module updated');
    }

    public function destroy(Request $request, int $module): JsonResponse
    {
        $m = $this->repo->find($module);
        if (! $m) return $this->notFound('Module not found');

        if ($request->boolean('detach_tenants', false)) {
            $this->service->detachFromAllTenants($m);
        }

        $this->service->destroy($m);
        return $this->noContent('Module deleted');
    }
}
