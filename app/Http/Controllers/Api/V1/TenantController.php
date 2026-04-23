<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Tenants\DTOs\UpdateTenantDTO;
use App\Domain\Tenants\Services\TenantService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\UpdateTenantRequest;
use App\Http\Resources\TenantResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly TenantService $tenantService) {}

    public function show(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant->load('subscriptionPlan');

        return $this->success(new TenantResource($tenant));
    }

    public function update(UpdateTenantRequest $request): JsonResponse
    {
        $tenant  = $request->user()->tenant;
        $updated = $this->tenantService->update($tenant, UpdateTenantDTO::fromRequest($request));

        return $this->success(new TenantResource($updated), 'Store settings updated');
    }
}
