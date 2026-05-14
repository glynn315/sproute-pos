<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Suppliers\DTOs\CreateSupplierDTO;
use App\Domain\Suppliers\DTOs\UpdateSupplierDTO;
use App\Domain\Suppliers\Repositories\SupplierRepository;
use App\Domain\Suppliers\Services\SupplierService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Suppliers\CreateSupplierRequest;
use App\Http\Requests\Suppliers\UpdateSupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly SupplierService    $supplierService,
        private readonly SupplierRepository $repo,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'is_active']);
        $perPage = min((int) $request->get('per_page', 20), 100);
        $suppliers = $this->repo->paginateForTenant($request->user()->tenant_id, $perPage, $filters);

        return $this->success(SupplierResource::collection($suppliers));
    }

    public function active(Request $request): JsonResponse
    {
        $suppliers = $this->repo->allActiveForTenant($request->user()->tenant_id);

        return $this->success(SupplierResource::collection($suppliers));
    }

    public function store(CreateSupplierRequest $request): JsonResponse
    {
        $supplier = $this->supplierService->create(CreateSupplierDTO::fromRequest($request));

        return $this->created(new SupplierResource($supplier), 'Supplier created');
    }

    public function show(Request $request, int $supplier): JsonResponse
    {
        $sup = $this->repo->findForTenant($request->user()->tenant_id, $supplier);

        if (! $sup) {
            return $this->notFound('Supplier not found');
        }

        return $this->success(new SupplierResource($sup->loadCount('products')));
    }

    public function update(UpdateSupplierRequest $request, int $supplier): JsonResponse
    {
        $sup = $this->repo->findForTenant($request->user()->tenant_id, $supplier);

        if (! $sup) {
            return $this->notFound('Supplier not found');
        }

        $updated = $this->supplierService->update($sup, UpdateSupplierDTO::fromRequest($request));

        return $this->success(new SupplierResource($updated), 'Supplier updated');
    }

    public function destroy(Request $request, int $supplier): JsonResponse
    {
        if (! $request->user()->canManage()) {
            return $this->forbidden();
        }

        $sup = $this->repo->findForTenant($request->user()->tenant_id, $supplier);

        if (! $sup) {
            return $this->notFound('Supplier not found');
        }

        $this->supplierService->delete($sup);

        return $this->noContent('Supplier deactivated');
    }
}
