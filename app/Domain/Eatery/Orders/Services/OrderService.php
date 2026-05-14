<?php

namespace App\Domain\Eatery\Orders\Services;

use App\Domain\Eatery\Menu\Models\MenuItem;
use App\Domain\Eatery\Orders\DTOs\AddOrderItemsDTO;
use App\Domain\Eatery\Orders\DTOs\CreateOrderDTO;
use App\Domain\Eatery\Orders\Models\Order;
use App\Domain\Eatery\Orders\Models\OrderItem;
use App\Domain\Eatery\Orders\Repositories\OrderRepository;
use App\Domain\Eatery\Tables\Models\RestaurantTable;
use App\Traits\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    use AuditLogger;

    public function __construct(private readonly OrderRepository $repo) {}

    public function create(CreateOrderDTO $dto): Order
    {
        return DB::transaction(function () use ($dto) {
            $table = RestaurantTable::where('tenant_id', $dto->tenantId)
                ->where('restaurant_table_id', $dto->restaurantTableId)
                ->lockForUpdate()
                ->first();

            if (! $table) {
                throw ValidationException::withMessages([
                    'restaurant_table_id' => ['Table not found for this tenant.'],
                ]);
            }

            // Business rule: a table cannot have two open (unpaid) orders.
            $existing = Order::where('tenant_id', $dto->tenantId)
                ->where('restaurant_table_id', $table->restaurant_table_id)
                ->where('payment_status', Order::PAYMENT_NOT_YET_PAID)
                ->exists();
            if ($existing) {
                throw ValidationException::withMessages([
                    'restaurant_table_id' => ['Table already has an unpaid order. Pay or cancel it first.'],
                ]);
            }

            $resolvedItems = $this->resolveItems($dto->tenantId, $dto->items);

            $subtotal = array_sum(array_map(fn ($r) => $r['subtotal'], $resolvedItems));

            $order = $this->repo->create([
                'tenant_id'           => $dto->tenantId,
                'restaurant_table_id' => $table->restaurant_table_id,
                'user_id'             => $dto->userId,
                'order_number'        => $this->repo->nextOrderNumber($dto->tenantId),
                'subtotal'            => $subtotal,
                'total_amount'        => $subtotal,
                'payment_status'      => Order::PAYMENT_NOT_YET_PAID,
                'notes'               => $dto->notes,
            ]);

            foreach ($resolvedItems as $ri) {
                OrderItem::create([
                    'order_id'     => $order->order_id,
                    'menu_item_id' => $ri['menu_item_id'],
                    'item_name'    => $ri['item_name'],
                    'price'        => $ri['price'],
                    'quantity'     => $ri['quantity'],
                    'subtotal'     => $ri['subtotal'],
                    'notes'        => $ri['notes'] ?? null,
                ]);
            }

            $this->transitionTable(
                table:      $table,
                newStatus:  RestaurantTable::STATUS_NOT_YET_PAID,
                reason:     'order_created',
                orderId:    $order->order_id,
                tenantId:   $dto->tenantId,
                userId:     $dto->userId,
            );

            $this->audit('created', 'Order', $order->order_id, null, [
                'order_number'        => $order->order_number,
                'total'               => $order->total_amount,
                'items'               => count($resolvedItems),
                'restaurant_table_id' => $table->restaurant_table_id,
            ], $dto->tenantId, $dto->userId);

            return $order->fresh(['items', 'table', 'cashier:id,name']);
        });
    }

    public function addItems(Order $order, AddOrderItemsDTO $dto): Order
    {
        if (! $order->isUnpaid()) {
            throw ValidationException::withMessages([
                'order' => ['Cannot add items to an already paid or cancelled order.'],
            ]);
        }

        return DB::transaction(function () use ($order, $dto) {
            $resolved = $this->resolveItems($order->tenant_id, $dto->items);

            foreach ($resolved as $ri) {
                OrderItem::create([
                    'order_id'     => $order->order_id,
                    'menu_item_id' => $ri['menu_item_id'],
                    'item_name'    => $ri['item_name'],
                    'price'        => $ri['price'],
                    'quantity'     => $ri['quantity'],
                    'subtotal'     => $ri['subtotal'],
                    'notes'        => $ri['notes'] ?? null,
                ]);
            }

            $newSubtotal = (float) OrderItem::where('order_id', $order->order_id)->sum('subtotal');
            $order->update([
                'subtotal'     => $newSubtotal,
                'total_amount' => $newSubtotal,
            ]);

            $this->audit('updated', 'Order', $order->order_id, null, [
                'added_items' => count($resolved),
                'new_total'   => $newSubtotal,
            ], $order->tenant_id);

            return $order->fresh(['items', 'table', 'cashier:id,name']);
        });
    }

    public function cancel(Order $order): Order
    {
        if (! $order->isUnpaid()) {
            throw ValidationException::withMessages([
                'order' => ['Only unpaid orders can be cancelled.'],
            ]);
        }

        return DB::transaction(function () use ($order) {
            $order->update(['payment_status' => Order::PAYMENT_CANCELLED]);

            // Free table if no other unpaid orders
            $stillUnpaid = Order::where('restaurant_table_id', $order->restaurant_table_id)
                ->where('payment_status', Order::PAYMENT_NOT_YET_PAID)
                ->exists();

            if (! $stillUnpaid && $order->table) {
                $this->transitionTable(
                    table:     $order->table,
                    newStatus: RestaurantTable::STATUS_AVAILABLE,
                    reason:    'order_cancelled',
                    orderId:   $order->order_id,
                    tenantId:  $order->tenant_id,
                );
            }

            $this->audit('cancelled', 'Order', $order->order_id, null, null, $order->tenant_id);

            return $order->fresh(['items', 'table']);
        });
    }

    /**
     * Update a table's status AND write a distinct audit row capturing the
     * cause. This is the single funnel for every status change — searching
     * audit_logs for entity_type='RestaurantTable' + action='status_changed'
     * gives you a per-table timeline for anomaly tracing.
     */
    private function transitionTable(
        RestaurantTable $table,
        string $newStatus,
        string $reason,
        ?int $orderId = null,
        ?int $paymentId = null,
        ?int $tenantId = null,
        ?int $userId = null,
    ): void {
        $from = $table->status;
        if ($from === $newStatus) return;          // no-op, don't pollute the log

        $table->update(['status' => $newStatus]);

        $this->audit(
            action:     'status_changed',
            entityType: 'RestaurantTable',
            entityId:   $table->restaurant_table_id,
            oldValues:  ['status' => $from],
            newValues:  array_filter([
                'status'     => $newStatus,
                'reason'     => $reason,
                'order_id'   => $orderId,
                'payment_id' => $paymentId,
            ], static fn ($v) => $v !== null),
            tenantId:   $tenantId ?? $table->tenant_id,
            userId:     $userId,
        );
    }

    /**
     * Validate menu items, compute per-line subtotal, and return enriched rows.
     */
    private function resolveItems(int $tenantId, array $items): array
    {
        if (empty($items)) {
            throw ValidationException::withMessages(['items' => ['At least one item is required.']]);
        }

        $resolved = [];
        foreach ($items as $row) {
            $menuItem = MenuItem::where('tenant_id', $tenantId)
                ->where('menu_item_id', $row['menu_item_id'])
                ->first();

            if (! $menuItem) {
                throw ValidationException::withMessages([
                    'items' => ["Menu item ID {$row['menu_item_id']} not found."],
                ]);
            }
            if (! $menuItem->availability) {
                throw ValidationException::withMessages([
                    'items' => ["Menu item '{$menuItem->name}' is not available."],
                ]);
            }

            $qty   = (int) $row['quantity'];
            $price = (float) $menuItem->price;
            $sub   = round($price * $qty, 2);

            $resolved[] = [
                'menu_item_id' => $menuItem->menu_item_id,
                'item_name'    => $menuItem->name,
                'price'        => $price,
                'quantity'     => $qty,
                'subtotal'     => $sub,
                'notes'        => $row['notes'] ?? null,
            ];
        }
        return $resolved;
    }
}
