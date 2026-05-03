<?php

namespace App\Domain\Sales\Services;

use App\Domain\Sales\DTOs\CreateSaleDTO;
use App\Domain\Sales\Repositories\SaleRepository;
use App\Models\InventoryLog;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Traits\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleService
{
    use AuditLogger;

    public function __construct(private readonly SaleRepository $repo) {}

    public function create(CreateSaleDTO $dto): Sale
    {
        return DB::transaction(function () use ($dto) {
            // Validate and price all items
            $resolvedItems = [];
            $subtotal      = 0;

            foreach ($dto->items as $item) {
                $product = Product::where('tenant_id', $dto->tenantId)
                    ->where('id', $item['product_id'])
                    ->where('is_active', true)
                    ->first();

                if (! $product) {
                    throw ValidationException::withMessages([
                        'items' => ["Product ID {$item['product_id']} not found or inactive."],
                    ]);
                }

                $qty      = (int) $item['quantity'];
                $discount = (float) ($item['discount'] ?? 0);

                if ($product->stock_quantity < $qty) {
                    throw ValidationException::withMessages([
                        'items' => ["Insufficient stock for '{$product->name}'. Available: {$product->stock_quantity}."],
                    ]);
                }

                $itemTotal = ($product->price * $qty) - $discount;

                $resolvedItems[] = [
                    'product'      => $product,
                    'quantity'     => $qty,
                    'unit_price'   => $product->price,
                    'discount'     => $discount,
                    'total'        => $itemTotal,
                ];

                $subtotal += $itemTotal;
            }

            $total      = $subtotal - $dto->discountAmount + $dto->taxAmount;
            $change     = max(0, $dto->amountPaid - $total);

            if ($dto->amountPaid < $total) {
                throw ValidationException::withMessages([
                    'amount_paid' => ["Amount paid ({$dto->amountPaid}) is less than total ({$total})."],
                ]);
            }

            // Create sale record
            $sale = $this->repo->create([
                'tenant_id'       => $dto->tenantId,
                'user_id'         => $dto->userId,
                'transaction_number' => $this->repo->nextTransactionNumber($dto->tenantId),
                'subtotal'        => $subtotal,
                'discount_amount' => $dto->discountAmount,
                'tax_amount'      => $dto->taxAmount,
                'total'           => $total,
                'amount_paid'     => $dto->amountPaid,
                'change_amount'   => $change,
                'payment_method'  => $dto->paymentMethod,
                'status'          => 'completed',
                'notes'           => $dto->notes,
            ]);

            // Create sale items and deduct stock
            foreach ($resolvedItems as $ri) {
                SaleItem::create([
                    'sale_id'      => $sale->id,
                    'product_id'   => $ri['product']->id,
                    'product_name' => $ri['product']->name,
                    'unit_price'   => $ri['unit_price'],
                    'quantity'     => $ri['quantity'],
                    'discount'     => $ri['discount'],
                    'total'        => $ri['total'],
                ]);

                // Coerce null → 0 defensively. The column is NOT NULL by schema,
                // but historical rows can carry NULL if they were inserted with
                // strict mode off; using $before|null below would NULL the log row.
                $before = (int) ($ri['product']->stock_quantity ?? 0);
                $after  = $before - $ri['quantity'];

                $ri['product']->update(['stock_quantity' => $after]);

                InventoryLog::create([
                    'tenant_id'       => $dto->tenantId,
                    'product_id'      => $ri['product']->id,
                    'user_id'         => $dto->userId,
                    'type'            => 'sale',
                    'quantity_change' => -$ri['quantity'],
                    'quantity_before' => $before,
                    'quantity_after'  => $after,
                    'reference_id'    => (string) $sale->id,
                    'reference_type'  => 'sale',
                ]);
            }

            $this->audit('created', 'Sale', $sale->id, null, ['total' => $total, 'items' => count($resolvedItems)], $dto->tenantId, $dto->userId);

            return $sale->load(['user:id,name', 'items.product:id,name,cost_price']);
        });
    }

    public function void(Sale $sale, int $userId): Sale
    {
        if (! $sale->canBeVoided()) {
            throw ValidationException::withMessages(['sale' => ['Only completed sales can be voided.']]);
        }

        return DB::transaction(function () use ($sale, $userId) {
            $sale->update(['status' => 'voided']);

            // Restore inventory
            foreach ($sale->items as $item) {
                $product = $item->product;
                if (! $product) {
                    // Product was deleted / FK orphaned — skip stock restore but
                    // continue voiding so the sale itself can be flagged.
                    continue;
                }

                // Coerce null → 0. inventory_logs.quantity_before is NOT NULL,
                // and using increment() on a NULL stock would leave it NULL.
                $before = (int) ($product->stock_quantity ?? 0);
                $after  = $before + $item->quantity;

                $product->update(['stock_quantity' => $after]);

                InventoryLog::create([
                    'tenant_id'       => $sale->tenant_id,
                    'product_id'      => $item->product_id,
                    'user_id'         => $userId,
                    'type'            => 'return',
                    'quantity_change' => $item->quantity,
                    'quantity_before' => $before,
                    'quantity_after'  => $after,
                    'reference_id'    => (string) $sale->id,
                    'reference_type'  => 'void',
                    'notes'           => "Sale voided: {$sale->transaction_number}",
                ]);
            }

            $this->audit('voided', 'Sale', $sale->id, ['status' => 'completed'], ['status' => 'voided'], $sale->tenant_id, $userId);

            return $sale->fresh(['user:id,name', 'items.product:id,name,cost_price']);
        });
    }
}
