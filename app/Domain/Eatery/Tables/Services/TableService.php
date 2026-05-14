<?php

namespace App\Domain\Eatery\Tables\Services;

use App\Domain\Eatery\Orders\Models\Order;
use App\Domain\Eatery\Tables\DTOs\StoreRestaurantTableDTO;
use App\Domain\Eatery\Tables\DTOs\UpdateRestaurantTableDTO;
use App\Domain\Eatery\Tables\Models\RestaurantTable;
use App\Domain\Eatery\Tables\Repositories\TableRepository;
use App\Traits\AuditLogger;
use Illuminate\Validation\ValidationException;

class TableService
{
    use AuditLogger;

    public function __construct(private readonly TableRepository $repo) {}

    public function create(StoreRestaurantTableDTO $dto): RestaurantTable
    {
        $exists = RestaurantTable::where('tenant_id', $dto->tenantId)
            ->where('table_number', $dto->tableNumber)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'table_number' => ["Table number {$dto->tableNumber} already exists."],
            ]);
        }

        $table = $this->repo->create([
            'tenant_id'    => $dto->tenantId,
            'table_number' => $dto->tableNumber,
            'label'        => $dto->label,
            'seats'        => $dto->seats,
            'status'       => RestaurantTable::STATUS_AVAILABLE,
            'notes'        => $dto->notes,
        ]);

        $this->audit('created', 'RestaurantTable', $table->restaurant_table_id, null, $table->toArray(), $dto->tenantId);

        return $table;
    }

    public function update(RestaurantTable $table, UpdateRestaurantTableDTO $dto): RestaurantTable
    {
        $old   = $table->toArray();
        $patch = $dto->toArray();

        if (isset($patch['table_number']) && (int) $patch['table_number'] !== (int) $table->table_number) {
            $clash = RestaurantTable::where('tenant_id', $table->tenant_id)
                ->where('table_number', $patch['table_number'])
                ->where('restaurant_table_id', '!=', $table->restaurant_table_id)
                ->exists();
            if ($clash) {
                throw ValidationException::withMessages([
                    'table_number' => ["Table number {$patch['table_number']} already exists."],
                ]);
            }
        }

        if (isset($patch['status']) && $patch['status'] === RestaurantTable::STATUS_AVAILABLE) {
            $hasUnpaid = Order::where('restaurant_table_id', $table->restaurant_table_id)
                ->where('payment_status', Order::PAYMENT_NOT_YET_PAID)
                ->exists();
            if ($hasUnpaid) {
                throw ValidationException::withMessages([
                    'status' => ['Table has an unpaid order; cannot mark available.'],
                ]);
            }
        }

        $table->fill($patch)->save();

        $this->audit('updated', 'RestaurantTable', $table->restaurant_table_id, $old, $table->fresh()->toArray(), $table->tenant_id);

        return $table->fresh('activeOrder');
    }

    public function destroy(RestaurantTable $table): void
    {
        $hasUnpaid = Order::where('restaurant_table_id', $table->restaurant_table_id)
            ->where('payment_status', Order::PAYMENT_NOT_YET_PAID)
            ->exists();

        if ($hasUnpaid) {
            throw ValidationException::withMessages([
                'table' => ['Cannot delete a table with an unpaid order.'],
            ]);
        }

        $old = $table->toArray();
        $table->delete();
        $this->audit('deleted', 'RestaurantTable', $old['restaurant_table_id'] ?? null, $old, null, $table->tenant_id);
    }

    public function markOccupied(RestaurantTable $table): RestaurantTable
    {
        if ($table->status !== RestaurantTable::STATUS_OCCUPIED) {
            $table->update(['status' => RestaurantTable::STATUS_OCCUPIED]);
        }
        return $table;
    }

    public function markAvailable(RestaurantTable $table): RestaurantTable
    {
        $table->update(['status' => RestaurantTable::STATUS_AVAILABLE]);
        return $table;
    }

    /**
     * Find every table for the tenant whose stored status disagrees with its
     * order state. Truth:
     *   - Has any unpaid order  ⇒ should be 'not_yet_paid'
     *   - No unpaid order       ⇒ should be 'available'
     */
    public function detectAnomalies(int $tenantId): array
    {
        $tables = RestaurantTable::where('tenant_id', $tenantId)->get();

        $anomalies = [];
        foreach ($tables as $t) {
            $unpaid = Order::where('restaurant_table_id', $t->restaurant_table_id)
                ->where('payment_status', Order::PAYMENT_NOT_YET_PAID)
                ->latest('order_id')
                ->first();

            $expected = $unpaid
                ? RestaurantTable::STATUS_NOT_YET_PAID
                : RestaurantTable::STATUS_AVAILABLE;

            // Treat 'occupied' as equivalent to 'not_yet_paid' for anomaly purposes —
            // a table is "occupied" iff there's an open order.
            $current = $t->status === RestaurantTable::STATUS_OCCUPIED
                ? RestaurantTable::STATUS_NOT_YET_PAID
                : $t->status;

            if ($current !== $expected) {
                $anomalies[] = [
                    'restaurant_table_id' => $t->restaurant_table_id,
                    'table_number'        => $t->table_number,
                    'label'               => $t->label,
                    'stored_status'       => $t->status,
                    'expected_status'     => $expected,
                    'active_order_id'     => $unpaid?->order_id,
                    'active_order_number' => $unpaid?->order_number,
                    'active_order_total'  => $unpaid ? (float) $unpaid->total_amount : null,
                ];
            }
        }

        return [
            'count'     => count($anomalies),
            'anomalies' => $anomalies,
        ];
    }

    /**
     * Heal every detected anomaly. Each fix writes a `status_changed` audit
     * row with reason='manual_sync' so the trail shows the correction.
     */
    public function syncStatuses(int $tenantId, ?int $userId = null): array
    {
        $detected = $this->detectAnomalies($tenantId);
        $healed   = 0;

        foreach ($detected['anomalies'] as $row) {
            $table = RestaurantTable::find($row['restaurant_table_id']);
            if (! $table) continue;

            $from = $table->status;
            $to   = $row['expected_status'];
            $table->update(['status' => $to]);

            $this->audit(
                action:     'status_changed',
                entityType: 'RestaurantTable',
                entityId:   $table->restaurant_table_id,
                oldValues:  ['status' => $from],
                newValues:  array_filter([
                    'status'   => $to,
                    'reason'   => 'manual_sync',
                    'order_id' => $row['active_order_id'] ?? null,
                ], static fn ($v) => $v !== null),
                tenantId:   $tenantId,
                userId:     $userId,
            );

            $healed++;
        }

        return [
            'detected' => $detected['count'],
            'healed'   => $healed,
        ];
    }
}
