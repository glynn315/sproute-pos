<?php

namespace App\Domain\Eatery\Dashboard\Services;

use App\Domain\Eatery\Orders\Models\Order;
use App\Domain\Eatery\Orders\Models\OrderItem;
use App\Domain\Eatery\Payments\Models\Payment;
use App\Domain\Eatery\Tables\Models\RestaurantTable;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EateryDashboardService
{
    public function summary(int $tenantId): array
    {
        $today = Carbon::today();

        $totalSalesToday = (float) Payment::where('tenant_id', $tenantId)
            ->whereDate('payment_date', $today)
            ->sum('total_amount');

        $totalOrdersToday = Order::where('tenant_id', $tenantId)
            ->whereDate('created_at', $today)
            ->count();

        $tableStatusCounts = RestaurantTable::where('tenant_id', $tenantId)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        return [
            'total_sales_today'  => $totalSalesToday,
            'total_orders_today' => $totalOrdersToday,
            'tables' => [
                'available'    => (int) ($tableStatusCounts[RestaurantTable::STATUS_AVAILABLE] ?? 0),
                'occupied'     => (int) ($tableStatusCounts[RestaurantTable::STATUS_OCCUPIED] ?? 0),
                'not_yet_paid' => (int) ($tableStatusCounts[RestaurantTable::STATUS_NOT_YET_PAID] ?? 0),
                'total'        => (int) $tableStatusCounts->sum(),
            ],
        ];
    }

    public function dailyReport(int $tenantId, ?string $date = null): array
    {
        $day = $date ? Carbon::parse($date)->startOfDay() : Carbon::today();

        $orders = Order::where('tenant_id', $tenantId)
            ->whereDate('created_at', $day);

        $paid      = (clone $orders)->where('payment_status', Order::PAYMENT_PAID)->count();
        $unpaid    = (clone $orders)->where('payment_status', Order::PAYMENT_NOT_YET_PAID)->count();
        $cancelled = (clone $orders)->where('payment_status', Order::PAYMENT_CANCELLED)->count();

        $revenue = (float) Payment::where('tenant_id', $tenantId)
            ->whereDate('payment_date', $day)
            ->sum('total_amount');

        return [
            'date'           => $day->toDateString(),
            'total_revenue'  => $revenue,
            'paid_orders'    => $paid,
            'unpaid_orders'  => $unpaid,
            'cancelled'      => $cancelled,
            'best_selling'   => $this->bestSelling($tenantId, $day, $day),
        ];
    }

    public function monthlyReport(int $tenantId, ?int $year = null, ?int $month = null): array
    {
        $start = Carbon::create($year ?? now()->year, $month ?? now()->month, 1)->startOfMonth();
        $end   = (clone $start)->endOfMonth();

        $orders = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$start, $end]);

        $paid      = (clone $orders)->where('payment_status', Order::PAYMENT_PAID)->count();
        $unpaid    = (clone $orders)->where('payment_status', Order::PAYMENT_NOT_YET_PAID)->count();
        $cancelled = (clone $orders)->where('payment_status', Order::PAYMENT_CANCELLED)->count();

        $revenue = (float) Payment::where('tenant_id', $tenantId)
            ->whereBetween('payment_date', [$start, $end])
            ->sum('total_amount');

        return [
            'period_start'   => $start->toDateString(),
            'period_end'     => $end->toDateString(),
            'total_revenue'  => $revenue,
            'paid_orders'    => $paid,
            'unpaid_orders'  => $unpaid,
            'cancelled'      => $cancelled,
            'best_selling'   => $this->bestSelling($tenantId, $start, $end),
        ];
    }

    private function bestSelling(int $tenantId, Carbon $from, Carbon $to, int $limit = 10): array
    {
        return OrderItem::query()
            ->join('orders', 'orders.order_id', '=', 'order_items.order_id')
            ->where('orders.tenant_id', $tenantId)
            ->whereBetween('orders.created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->where('orders.payment_status', Order::PAYMENT_PAID)
            ->select(
                'order_items.menu_item_id',
                'order_items.item_name',
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.subtotal) as total_revenue'),
            )
            ->groupBy('order_items.menu_item_id', 'order_items.item_name')
            ->orderByDesc('total_quantity')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => [
                'menu_item_id'   => $r->menu_item_id,
                'item_name'      => $r->item_name,
                'total_quantity' => (int) $r->total_quantity,
                'total_revenue'  => (float) $r->total_revenue,
            ])
            ->toArray();
    }
}
