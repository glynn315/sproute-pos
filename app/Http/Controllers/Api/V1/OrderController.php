<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Eatery\Orders\DTOs\AddOrderItemsDTO;
use App\Domain\Eatery\Orders\DTOs\CreateOrderDTO;
use App\Domain\Eatery\Orders\Repositories\OrderRepository;
use App\Domain\Eatery\Orders\Services\OrderService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Eatery\Orders\AddOrderItemsRequest;
use App\Http\Requests\Eatery\Orders\CreateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly OrderService    $service,
        private readonly OrderRepository $repo,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['payment_status', 'table_id', 'date_from', 'date_to', 'search']);
        $perPage = min((int) $request->get('per_page', 20), 100);
        $orders  = $this->repo->paginateForTenant($request->user()->tenant_id, $perPage, $filters);

        return $this->success(OrderResource::collection($orders));
    }

    public function show(Request $request, int $order): JsonResponse
    {
        $row = $this->repo->findForTenant($request->user()->tenant_id, $order);
        if (! $row) return $this->notFound('Order not found');
        return $this->success(new OrderResource($row));
    }

    public function store(CreateOrderRequest $request): JsonResponse
    {
        $order = $this->service->create(CreateOrderDTO::fromRequest($request, $request->user()));
        return $this->created(new OrderResource($order), 'Order created');
    }

    public function addItems(AddOrderItemsRequest $request, int $order): JsonResponse
    {
        $row = $this->repo->findForTenant($request->user()->tenant_id, $order);
        if (! $row) return $this->notFound('Order not found');

        $updated = $this->service->addItems($row, AddOrderItemsDTO::fromRequest($request));
        return $this->success(new OrderResource($updated), 'Items added');
    }

    public function cancel(Request $request, int $order): JsonResponse
    {
        $row = $this->repo->findForTenant($request->user()->tenant_id, $order);
        if (! $row) return $this->notFound('Order not found');

        $cancelled = $this->service->cancel($row);
        return $this->success(new OrderResource($cancelled), 'Order cancelled');
    }

    public function activeForTable(Request $request, int $table): JsonResponse
    {
        $order = $this->repo->findUnpaidForTable($request->user()->tenant_id, $table);
        if (! $order) return $this->success(null, 'No unpaid order for table');
        return $this->success(new OrderResource($order));
    }
}
