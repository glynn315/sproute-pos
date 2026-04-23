<?php

namespace App\Domain\Dashboard\Services;

use App\Models\Expense;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getSummary(int $tenantId, string $period = 'today'): array
    {
        [$from, $to] = $this->resolvePeriod($period);

        $salesQuery = Sale::where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$from, $to]);

        $revenue = (float) $salesQuery->sum('total');

        // Cost of goods sold
        $cogs = (float) SaleItem::join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->where('sales.tenant_id', $tenantId)
            ->where('sales.status', 'completed')
            ->whereBetween('sales.created_at', [$from, $to])
            ->sum(DB::raw('sale_items.quantity * products.cost_price'));

        $expenses = (float) Expense::where('tenant_id', $tenantId)
            ->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])
            ->sum('amount');

        $grossProfit = $revenue - $cogs;
        $netProfit   = $grossProfit - $expenses;

        $transactionCount = $salesQuery->count();

        $avgTransactionValue = $transactionCount > 0
            ? round($revenue / $transactionCount, 2)
            : 0;

        return [
            'period'               => $period,
            'from'                 => $from->toDateTimeString(),
            'to'                   => $to->toDateTimeString(),
            'revenue'              => $revenue,
            'cogs'                 => $cogs,
            'gross_profit'         => $grossProfit,
            'expenses'             => $expenses,
            'net_profit'           => $netProfit,
            'transaction_count'    => $transactionCount,
            'avg_transaction_value'=> $avgTransactionValue,
        ];
    }

    public function getSalesReport(int $tenantId, array $filters = []): array
    {
        $from = $filters['date_from'] ?? now()->startOfMonth()->toDateString();
        $to   = $filters['date_to']   ?? now()->endOfMonth()->toDateString();

        $sales = Sale::with(['user:id,name', 'items'])
            ->where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->orderByDesc('created_at')
            ->get();

        $totalRevenue = $sales->sum('total');
        $totalDiscount = $sales->sum('discount_amount');

        $byPaymentMethod = $sales->groupBy('payment_method')
            ->map(fn ($group) => [
                'count'   => $group->count(),
                'total'   => round($group->sum('total'), 2),
            ])->toArray();

        $dailyBreakdown = $sales->groupBy(fn ($s) => $s->created_at->toDateString())
            ->map(fn ($group) => [
                'count'   => $group->count(),
                'revenue' => round($group->sum('total'), 2),
            ])->toArray();

        return [
            'from'              => $from,
            'to'                => $to,
            'total_revenue'     => round($totalRevenue, 2),
            'total_discount'    => round($totalDiscount, 2),
            'transaction_count' => $sales->count(),
            'by_payment_method' => $byPaymentMethod,
            'daily_breakdown'   => $dailyBreakdown,
        ];
    }

    public function getExpenseReport(int $tenantId, array $filters = []): array
    {
        $from = $filters['date_from'] ?? now()->startOfMonth()->toDateString();
        $to   = $filters['date_to']   ?? now()->endOfMonth()->toDateString();

        $expenses = Expense::where('tenant_id', $tenantId)
            ->whereBetween('expense_date', [$from, $to])
            ->get();

        $total = $expenses->sum('amount');

        $byCategory = $expenses->groupBy('category')
            ->map(fn ($group) => [
                'count'  => $group->count(),
                'amount' => round($group->sum('amount'), 2),
            ])->toArray();

        return [
            'from'        => $from,
            'to'          => $to,
            'total'       => round($total, 2),
            'count'       => $expenses->count(),
            'by_category' => $byCategory,
        ];
    }

    public function getTopProducts(int $tenantId, int $limit = 10, string $period = 'this_month'): array
    {
        [$from, $to] = $this->resolvePeriod($period);

        return SaleItem::select(
                'sale_items.product_id',
                'sale_items.product_name',
                DB::raw('SUM(sale_items.quantity) as total_quantity'),
                DB::raw('SUM(sale_items.total) as total_revenue')
            )
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.tenant_id', $tenantId)
            ->where('sales.status', 'completed')
            ->whereBetween('sales.created_at', [$from, $to])
            ->groupBy('sale_items.product_id', 'sale_items.product_name')
            ->orderByDesc('total_quantity')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    private function resolvePeriod(string $period): array
    {
        return match ($period) {
            'today'      => [now()->startOfDay(), now()->endOfDay()],
            'yesterday'  => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
            'this_week'  => [now()->startOfWeek(), now()->endOfWeek()],
            'last_week'  => [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()],
            'this_month' => [now()->startOfMonth(), now()->endOfMonth()],
            'last_month' => [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()],
            'this_year'  => [now()->startOfYear(), now()->endOfYear()],
            default      => [now()->startOfDay(), now()->endOfDay()],
        };
    }
}
