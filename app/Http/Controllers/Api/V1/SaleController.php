<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Sales\DTOs\CreateSaleDTO;
use App\Domain\Sales\Repositories\SaleRepository;
use App\Domain\Sales\Services\SaleService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\CreateSaleRequest;
use App\Http\Resources\SaleResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly SaleService    $saleService,
        private readonly SaleRepository $repo,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'payment_method', 'date_from', 'date_to', 'search']);
        $perPage = min((int) $request->get('per_page', 20), 100);
        $sales   = $this->repo->paginateForTenant($request->user()->tenant_id, $perPage, $filters);

        return $this->success(SaleResource::collection($sales));
    }

    public function store(CreateSaleRequest $request): JsonResponse
    {
        $sale = $this->saleService->create(
            CreateSaleDTO::fromRequest($request, $request->user())
        );

        return $this->created(new SaleResource($sale), 'Sale completed');
    }

    public function show(Request $request, int $sale): JsonResponse
    {
        $s = $this->repo->findForTenant($request->user()->tenant_id, $sale);

        if (! $s) {
            return $this->notFound('Sale not found');
        }

        return $this->success(new SaleResource($s));
    }

    public function void(Request $request, int $sale): JsonResponse
    {
        if (! $request->user()->canManage()) {
            return $this->forbidden('Only managers or owners can void sales.');
        }

        $s = $this->repo->findForTenant($request->user()->tenant_id, $sale);

        if (! $s) {
            return $this->notFound('Sale not found');
        }

        $voided = $this->saleService->void($s, $request->user()->id);

        return $this->success(new SaleResource($voided), 'Sale voided and inventory restored');
    }
}
