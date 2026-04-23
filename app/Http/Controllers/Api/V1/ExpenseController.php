<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Expenses\DTOs\CreateExpenseDTO;
use App\Domain\Expenses\Repositories\ExpenseRepository;
use App\Domain\Expenses\Services\ExpenseService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Expenses\CreateExpenseRequest;
use App\Http\Requests\Expenses\UpdateExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ExpenseService    $expenseService,
        private readonly ExpenseRepository $repo,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters  = $request->only(['category', 'date_from', 'date_to']);
        $perPage  = min((int) $request->get('per_page', 20), 100);
        $expenses = $this->repo->paginateForTenant($request->user()->tenant_id, $perPage, $filters);

        return $this->success(ExpenseResource::collection($expenses));
    }

    public function store(CreateExpenseRequest $request): JsonResponse
    {
        $expense = $this->expenseService->create(CreateExpenseDTO::fromRequest($request));

        return $this->created(new ExpenseResource($expense), 'Expense recorded');
    }

    public function show(Request $request, int $expense): JsonResponse
    {
        $exp = $this->repo->findForTenant($request->user()->tenant_id, $expense);

        if (! $exp) {
            return $this->notFound('Expense not found');
        }

        return $this->success(new ExpenseResource($exp));
    }

    public function update(UpdateExpenseRequest $request, int $expense): JsonResponse
    {
        $exp = $this->repo->findForTenant($request->user()->tenant_id, $expense);

        if (! $exp) {
            return $this->notFound('Expense not found');
        }

        $updated = $this->expenseService->update($exp, $request->validated());

        return $this->success(new ExpenseResource($updated), 'Expense updated');
    }

    public function destroy(Request $request, int $expense): JsonResponse
    {
        if (! $request->user()->canAdminTenant()) {
            return $this->forbidden();
        }

        $exp = $this->repo->findForTenant($request->user()->tenant_id, $expense);

        if (! $exp) {
            return $this->notFound('Expense not found');
        }

        $this->expenseService->delete($exp);

        return $this->noContent('Expense deleted');
    }
}
