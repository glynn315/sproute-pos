<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Eatery\Dashboard\Services\EateryDashboardService;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EateryDashboardController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly EateryDashboardService $service) {}

    public function summary(Request $request): JsonResponse
    {
        return $this->success($this->service->summary($request->user()->tenant_id));
    }

    public function daily(Request $request): JsonResponse
    {
        return $this->success($this->service->dailyReport(
            $request->user()->tenant_id,
            $request->query('date'),
        ));
    }

    public function monthly(Request $request): JsonResponse
    {
        return $this->success($this->service->monthlyReport(
            $request->user()->tenant_id,
            $request->query('year') ? (int) $request->query('year') : null,
            $request->query('month') ? (int) $request->query('month') : null,
        ));
    }
}
