<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Dashboard\Services\DashboardService;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly DashboardService $dashboardService) {}

    public function summary(Request $request): JsonResponse
    {
        $period  = $request->get('period', 'today');
        $summary = $this->dashboardService->getSummary($request->user()->tenant_id, $period);

        return $this->success($summary);
    }

    public function salesReport(Request $request): JsonResponse
    {
        $filters = $request->only(['date_from', 'date_to']);
        $report  = $this->dashboardService->getSalesReport($request->user()->tenant_id, $filters);

        return $this->success($report);
    }

    public function expenseReport(Request $request): JsonResponse
    {
        $filters = $request->only(['date_from', 'date_to']);
        $report  = $this->dashboardService->getExpenseReport($request->user()->tenant_id, $filters);

        return $this->success($report);
    }

    public function topProducts(Request $request): JsonResponse
    {
        $products = $this->dashboardService->getTopProducts(
            $request->user()->tenant_id,
            (int) $request->get('limit', 10),
            $request->get('period', 'this_month')
        );

        return $this->success($products);
    }
}
