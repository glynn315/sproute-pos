<?php

namespace App\Domain\Eatery\Payments\Services;

use App\Domain\Eatery\Orders\Models\Order;
use App\Domain\Eatery\Payments\DTOs\CreatePaymentDTO;
use App\Domain\Eatery\Payments\Models\Payment;
use App\Domain\Eatery\Payments\Repositories\PaymentRepository;
use App\Domain\Eatery\Tables\Models\RestaurantTable;
use App\Traits\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    use AuditLogger;

    public function __construct(private readonly PaymentRepository $repo) {}

    public function pay(CreatePaymentDTO $dto): Payment
    {
        return DB::transaction(function () use ($dto) {
            $order = $this->resolveTargetOrder($dto);

            if ($order->isPaid()) {
                throw ValidationException::withMessages([
                    'order' => ['Order is already paid.'],
                ]);
            }
            if ($order->payment_status === Order::PAYMENT_CANCELLED) {
                throw ValidationException::withMessages([
                    'order' => ['Cancelled orders cannot be paid.'],
                ]);
            }

            $total  = (float) $order->total_amount;
            $cash   = round($dto->cashReceived, 2);

            if ($cash < $total) {
                throw ValidationException::withMessages([
                    'cash_received' => ["Insufficient payment. Cash {$cash} is less than total {$total}."],
                ]);
            }

            $change = round($cash - $total, 2);

            $payment = $this->repo->create([
                'tenant_id'      => $dto->tenantId,
                'order_id'       => $order->order_id,
                'user_id'        => $dto->userId,
                'total_amount'   => $total,
                'cash_received'  => $cash,
                'change_amount'  => $change,
                'payment_method' => $dto->paymentMethod,
                'payment_date'   => now(),
                'reference'      => $dto->reference,
                'notes'          => $dto->notes,
            ]);

            $order->update(['payment_status' => Order::PAYMENT_PAID]);

            // Free the table if there are no other unpaid orders on it
            if ($order->table) {
                $stillUnpaid = Order::where('restaurant_table_id', $order->restaurant_table_id)
                    ->where('payment_status', Order::PAYMENT_NOT_YET_PAID)
                    ->exists();
                if (! $stillUnpaid) {
                    $from = $order->table->status;
                    if ($from !== RestaurantTable::STATUS_AVAILABLE) {
                        $order->table->update(['status' => RestaurantTable::STATUS_AVAILABLE]);

                        $this->audit(
                            action:     'status_changed',
                            entityType: 'RestaurantTable',
                            entityId:   $order->table->restaurant_table_id,
                            oldValues:  ['status' => $from],
                            newValues:  [
                                'status'     => RestaurantTable::STATUS_AVAILABLE,
                                'reason'     => 'payment_received',
                                'order_id'   => $order->order_id,
                                'payment_id' => $payment->payment_id,
                            ],
                            tenantId:   $dto->tenantId,
                            userId:     $dto->userId,
                        );
                    }
                }
            }

            $this->audit('created', 'Payment', $payment->payment_id, null, [
                'order_id'       => $order->order_id,
                'order_number'   => $order->order_number,
                'total'          => $total,
                'cash_received'  => $cash,
                'change'         => $change,
                'payment_method' => $dto->paymentMethod,
            ], $dto->tenantId, $dto->userId);

            return $payment->fresh(['order.items', 'order.table', 'cashier:id,name']);
        });
    }

    private function resolveTargetOrder(CreatePaymentDTO $dto): Order
    {
        if ($dto->orderId) {
            $order = Order::with(['items', 'table'])
                ->where('tenant_id', $dto->tenantId)
                ->where('order_id', $dto->orderId)
                ->lockForUpdate()
                ->first();

            if (! $order) {
                throw ValidationException::withMessages(['order_id' => ['Order not found.']]);
            }
            return $order;
        }

        if ($dto->restaurantTableId) {
            $order = Order::with(['items', 'table'])
                ->where('tenant_id', $dto->tenantId)
                ->where('restaurant_table_id', $dto->restaurantTableId)
                ->where('payment_status', Order::PAYMENT_NOT_YET_PAID)
                ->lockForUpdate()
                ->latest('order_id')
                ->first();

            if (! $order) {
                throw ValidationException::withMessages([
                    'table_id' => ['No unpaid order found for this table.'],
                ]);
            }
            return $order;
        }

        throw ValidationException::withMessages([
            'order' => ['Either order_id or table_id is required.'],
        ]);
    }
}
