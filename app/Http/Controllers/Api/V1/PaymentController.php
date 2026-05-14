<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Eatery\Payments\DTOs\CreatePaymentDTO;
use App\Domain\Eatery\Payments\Repositories\PaymentRepository;
use App\Domain\Eatery\Payments\Services\PaymentService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Eatery\Payments\CreatePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly PaymentService    $service,
        private readonly PaymentRepository $repo,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters  = $request->only(['date_from', 'date_to', 'payment_method', 'order_id']);
        $perPage  = min((int) $request->get('per_page', 20), 100);
        $payments = $this->repo->paginateForTenant($request->user()->tenant_id, $perPage, $filters);

        return $this->success(PaymentResource::collection($payments));
    }

    public function show(Request $request, int $payment): JsonResponse
    {
        $row = $this->repo->findForTenant($request->user()->tenant_id, $payment);
        if (! $row) return $this->notFound('Payment not found');
        return $this->success(new PaymentResource($row));
    }

    public function store(CreatePaymentRequest $request): JsonResponse
    {
        $payment = $this->service->pay(CreatePaymentDTO::fromRequest($request, $request->user()));
        return $this->created(new PaymentResource($payment), 'Payment processed');
    }
}
