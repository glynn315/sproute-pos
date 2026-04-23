<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Employees\DTOs\CreateEmployeeDTO;
use App\Domain\Employees\DTOs\UpdateEmployeeDTO;
use App\Domain\Employees\Repositories\EmployeeRepository;
use App\Domain\Employees\Services\EmployeeService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Employees\CreateEmployeeRequest;
use App\Http\Requests\Employees\UpdateEmployeeRequest;
use App\Http\Resources\UserResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly EmployeeService    $employeeService,
        private readonly EmployeeRepository $repo,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $employees = $this->repo->allForTenant($request->user()->tenant_id);

        return $this->success(UserResource::collection($employees));
    }

    public function store(CreateEmployeeRequest $request): JsonResponse
    {
        $tenant   = $request->user()->tenant;
        $employee = $this->employeeService->create(CreateEmployeeDTO::fromRequest($request), $tenant);

        return $this->created(new UserResource($employee), 'Employee created');
    }

    public function show(Request $request, int $employee): JsonResponse
    {
        $emp = $this->repo->findForTenant($request->user()->tenant_id, $employee);

        if (! $emp) {
            return $this->notFound('Employee not found');
        }

        return $this->success(new UserResource($emp));
    }

    public function update(UpdateEmployeeRequest $request, int $employee): JsonResponse
    {
        $emp = $this->repo->findForTenant($request->user()->tenant_id, $employee);

        if (! $emp) {
            return $this->notFound('Employee not found');
        }

        $updated = $this->employeeService->update($emp, UpdateEmployeeDTO::fromRequest($request));

        return $this->success(new UserResource($updated), 'Employee updated');
    }

    public function destroy(Request $request, int $employee): JsonResponse
    {
        if (! $request->user()->canAdminTenant()) {
            return $this->forbidden();
        }

        $emp = $this->repo->findForTenant($request->user()->tenant_id, $employee);

        if (! $emp) {
            return $this->notFound('Employee not found');
        }

        $this->employeeService->delete($emp);

        return $this->noContent('Employee removed');
    }
}
