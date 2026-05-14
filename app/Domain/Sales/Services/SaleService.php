<?php

namespace App\Domain\Sales\Services;

use App\Domain\Sales\DTOs\CreateSaleDTO;
use App\Domain\Sales\Repositories\SaleRepository;
use App\Models\InventoryLog;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\SaleRefund;
use App\Models\SaleRefundItem;
use App\Models\Tenant;
use App\Traits\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleService
{
    use AuditLogger;

    public function __construct(private readonly SaleRepository $repo) {}

    /**
     * Create a sale. If $dto->asDraft is true, the sale is saved with status='draft',
     * no stock is deducted, and no payments are persisted. Otherwise the sale is
     * completed in the same transaction (legacy flow, with optional split-payment).
     */
    public function create(CreateSaleDTO $dto): Sale
    {
        return DB::transaction(function () use ($dto) {
            // Validate and price all items (stock check only when not a draft)
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

                if (! $dto->asDraft && $product->stock_quantity < $qty) {
                    throw ValidationException::withMessages([
                        'items' => ["Insufficient stock for '{$product->name}'. Available: {$product->stock_quantity}."],
                    ]);
                }

                $itemTotal = ($product->price * $qty) - $discount;

                $resolvedItems[] = [
                    'product'    => $product,
                    'quantity'   => $qty,
                    'unit_price' => $product->price,
                    'discount'   => $discount,
                    'total'      => $itemTotal,
                ];

                $subtotal += $itemTotal;
            }

            $total  = $subtotal - $dto->discountAmount + $dto->taxAmount;
            $change = max(0, $dto->amountPaid - $total);

            if (! $dto->asDraft && $dto->amountPaid < $total) {
                throw ValidationException::withMessages([
                    'amount_paid' => ["Amount paid ({$dto->amountPaid}) is less than total ({$total})."],
                ]);
            }

            if (! $dto->asDraft && is_array($dto->payments)) {
                $this->ensurePaymentsCoverTotal($dto->payments, $total);
            }

            $status = $dto->asDraft ? 'draft' : 'completed';

            // Create sale record
            $sale = $this->repo->create([
                'tenant_id'          => $dto->tenantId,
                'user_id'            => $dto->userId,
                'transaction_number' => $this->repo->nextTransactionNumber($dto->tenantId),
                'subtotal'           => $subtotal,
                'discount_amount'    => $dto->discountAmount,
                'tax_amount'         => $dto->taxAmount,
                'total'              => $total,
                'amount_paid'        => $dto->asDraft ? 0 : $dto->amountPaid,
                'change_amount'      => $dto->asDraft ? 0 : $change,
                'payment_method'     => $dto->paymentMethod ?: 'cash',
                'status'             => $status,
                'suspended_at'       => $dto->asDraft ? now() : null,
                'notes'              => $dto->notes,
            ]);

            // Items snapshot (always created, even for drafts so resume re-uses them)
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

                if (! $dto->asDraft) {
                    $this->deductStock(
                        product:  $ri['product'],
                        quantity: $ri['quantity'],
                        saleId:   $sale->id,
                        userId:   $dto->userId,
                        tenantId: $dto->tenantId,
                    );
                }
            }

            if (! $dto->asDraft) {
                $this->persistPayments($sale, $dto->payments, $dto->paymentMethod, $dto->amountPaid);
            }

            $this->audit(
                action:    $dto->asDraft ? 'draft_created' : 'created',
                entityType:'Sale',
                entityId:  $sale->id,
                oldValues: null,
                newValues: ['total' => $total, 'items' => count($resolvedItems), 'status' => $status],
                tenantId:  $dto->tenantId,
                userId:    $dto->userId,
            );

            return $sale->load(['user:id,name', 'items.product:id,name,cost_price', 'payments']);
        });
    }

    /**
     * Suspend an in-progress sale (must already be a draft we created via create() with as_draft).
     * In current design, a sale is created as draft directly; suspend simply attaches an optional note.
     */
    public function suspend(Sale $sale, ?string $note, int $userId): Sale
    {
        if (! $sale->isDraft()) {
            throw ValidationException::withMessages(['sale' => ['Only draft sales can be suspended.']]);
        }

        $sale->update([
            'suspended_at'    => now(),
            'suspension_note' => $note,
        ]);

        $this->audit('suspended', 'Sale', $sale->id, null, ['note' => $note], $sale->tenant_id, $userId);

        return $sale->fresh(['items', 'payments']);
    }

    /**
     * Commit a draft sale into a completed sale: deducts stock, records payments, audits.
     */
    public function commitDraft(Sale $sale, array $payload, int $userId): Sale
    {
        if (! $sale->canBeCommitted()) {
            throw ValidationException::withMessages(['sale' => ['Sale is not in draft state.']]);
        }

        return DB::transaction(function () use ($sale, $payload, $userId) {
            $discountAmount = (float) ($payload['discount_amount'] ?? $sale->discount_amount);
            $taxAmount      = (float) ($payload['tax_amount'] ?? $sale->tax_amount);
            $paymentMethod  = $payload['payment_method'] ?? $sale->payment_method ?? 'cash';
            $amountPaid     = (float) $payload['amount_paid'];
            $payments       = $payload['payments'] ?? null;

            $subtotal = (float) $sale->items->sum('total');
            $total    = $subtotal - $discountAmount + $taxAmount;
            $change   = max(0, $amountPaid - $total);

            if ($amountPaid < $total) {
                throw ValidationException::withMessages([
                    'amount_paid' => ["Amount paid ({$amountPaid}) is less than total ({$total})."],
                ]);
            }

            if (is_array($payments)) {
                $this->ensurePaymentsCoverTotal($payments, $total);
            }

            foreach ($sale->items as $item) {
                $product = Product::where('tenant_id', $sale->tenant_id)
                    ->where('id', $item->product_id)
                    ->where('is_active', true)
                    ->first();

                if (! $product) {
                    throw ValidationException::withMessages([
                        'items' => ["Product ID {$item->product_id} no longer available."],
                    ]);
                }

                if ($product->stock_quantity < $item->quantity) {
                    throw ValidationException::withMessages([
                        'items' => ["Insufficient stock for '{$product->name}'. Available: {$product->stock_quantity}."],
                    ]);
                }

                $this->deductStock(
                    product:  $product,
                    quantity: $item->quantity,
                    saleId:   $sale->id,
                    userId:   $userId,
                    tenantId: $sale->tenant_id,
                );
            }

            $sale->update([
                'subtotal'        => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_amount'      => $taxAmount,
                'total'           => $total,
                'amount_paid'     => $amountPaid,
                'change_amount'   => $change,
                'payment_method'  => $paymentMethod,
                'status'          => 'completed',
                'suspended_at'    => null,
                'suspension_note' => null,
                'notes'           => $payload['notes'] ?? $sale->notes,
            ]);

            $this->persistPayments($sale, $payments, $paymentMethod, $amountPaid);

            $this->audit(
                action:    'committed',
                entityType:'Sale',
                entityId:  $sale->id,
                oldValues: ['status' => 'draft'],
                newValues: ['status' => 'completed', 'total' => $total],
                tenantId:  $sale->tenant_id,
                userId:    $userId,
            );

            return $sale->fresh(['user:id,name', 'items.product:id,name,cost_price', 'payments']);
        });
    }

    /**
     * Void a fully-completed sale (legacy behavior). Restores ALL inventory.
     */
    public function void(Sale $sale, int $userId): Sale
    {
        if (! $sale->canBeVoided()) {
            throw ValidationException::withMessages(['sale' => ['Only completed sales can be voided.']]);
        }

        return DB::transaction(function () use ($sale, $userId) {
            $sale->update(['status' => 'voided']);

            foreach ($sale->items as $item) {
                $product = $item->product;
                if (! $product) {
                    continue;
                }

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

            return $sale->fresh(['user:id,name', 'items.product:id,name,cost_price', 'payments', 'refunds']);
        });
    }

    /**
     * Partially or fully refund a completed sale. Restores stock per-item.
     * If every item is fully refunded the sale transitions to 'refunded';
     * otherwise it transitions to 'partially_refunded'.
     *
     * @param array<int, array{sale_item_id:int, quantity:int}> $itemRefunds
     */
    public function refund(Sale $sale, array $itemRefunds, string $reason, ?string $refundMethod, int $userId): SaleRefund
    {
        if (! $sale->canBeRefunded()) {
            throw ValidationException::withMessages(['sale' => ['Sale cannot be refunded in its current state.']]);
        }

        return DB::transaction(function () use ($sale, $itemRefunds, $reason, $refundMethod, $userId) {
            // Build map of already-refunded quantities per sale_item for cap check
            $alreadyRefunded = SaleRefundItem::query()
                ->whereIn('sale_item_id', $sale->items->pluck('id'))
                ->selectRaw('sale_item_id, SUM(quantity) as qty')
                ->groupBy('sale_item_id')
                ->pluck('qty', 'sale_item_id');

            $refund = SaleRefund::create([
                'sale_id'             => $sale->id,
                'refunded_by_user_id' => $userId,
                'total_refunded'      => 0, // updated below
                'reason'              => $reason,
                'refund_method'       => $refundMethod ?: 'cash',
            ]);

            $totalRefunded = 0.0;

            foreach ($itemRefunds as $r) {
                $item = $sale->items->firstWhere('id', (int) $r['sale_item_id']);
                if (! $item) {
                    throw ValidationException::withMessages(['items' => ["Sale item {$r['sale_item_id']} not found in this sale."]]);
                }

                $qty             = (int) $r['quantity'];
                $previouslyDone  = (int) ($alreadyRefunded[$item->id] ?? 0);
                $remaining       = $item->quantity - $previouslyDone;

                if ($qty < 1) {
                    throw ValidationException::withMessages(['items' => ["Quantity for refund must be at least 1."]]);
                }
                if ($qty > $remaining) {
                    throw ValidationException::withMessages([
                        'items' => ["Cannot refund {$qty} of '{$item->product_name}'. Remaining refundable: {$remaining}."],
                    ]);
                }

                // Per-unit price net of discount, prorated across qty
                $perUnit  = $item->quantity > 0 ? (float) $item->total / $item->quantity : 0.0;
                $amount   = round($perUnit * $qty, 2);

                SaleRefundItem::create([
                    'sale_refund_id' => $refund->id,
                    'sale_item_id'   => $item->id,
                    'quantity'       => $qty,
                    'amount'         => $amount,
                ]);

                $totalRefunded += $amount;

                // Restore stock
                $product = $item->product;
                if ($product) {
                    $before = (int) ($product->stock_quantity ?? 0);
                    $after  = $before + $qty;
                    $product->update(['stock_quantity' => $after]);

                    InventoryLog::create([
                        'tenant_id'       => $sale->tenant_id,
                        'product_id'      => $product->id,
                        'user_id'         => $userId,
                        'type'            => 'return',
                        'quantity_change' => $qty,
                        'quantity_before' => $before,
                        'quantity_after'  => $after,
                        'reference_id'    => (string) $refund->id,
                        'reference_type'  => 'refund',
                        'notes'           => "Refund for {$sale->transaction_number}: {$reason}",
                    ]);
                }
            }

            $refund->update(['total_refunded' => $totalRefunded]);

            // Decide overall sale status (refunded vs partially_refunded)
            $remainingByItem = $sale->items->mapWithKeys(function ($item) use ($alreadyRefunded, $itemRefunds) {
                $previous = (int) ($alreadyRefunded[$item->id] ?? 0);
                $now      = 0;
                foreach ($itemRefunds as $r) {
                    if ((int) $r['sale_item_id'] === $item->id) {
                        $now += (int) $r['quantity'];
                    }
                }
                return [$item->id => $item->quantity - $previous - $now];
            });

            $allItemsFullyRefunded = $remainingByItem->every(fn ($qty) => $qty <= 0);
            $newStatus             = $allItemsFullyRefunded ? 'refunded' : 'partially_refunded';

            $sale->update(['status' => $newStatus]);

            $this->audit(
                action:    $newStatus,
                entityType:'Sale',
                entityId:  $sale->id,
                oldValues: ['status' => $sale->getOriginal('status')],
                newValues: ['status' => $newStatus, 'refund_id' => $refund->id, 'total_refunded' => $totalRefunded],
                tenantId:  $sale->tenant_id,
                userId:    $userId,
            );

            return $refund->fresh(['items', 'refundedBy:id,name']);
        });
    }

    /**
     * Build a printable receipt payload for a sale (header, body lines, totals, footer).
     */
    public function buildReceipt(Sale $sale): array
    {
        $tenant = Tenant::find($sale->tenant_id);

        $lines = [];
        foreach ($sale->items as $item) {
            $lines[] = [
                'name'       => $item->product_name,
                'quantity'   => $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'discount'   => (float) $item->discount,
                'total'      => (float) $item->total,
            ];
        }

        $payments = $sale->payments->map(fn ($p) => [
            'method'       => $p->payment_method,
            'amount'       => (float) $p->amount,
            'reference_no' => $p->reference_no,
        ])->all();

        return [
            'receipt_no'         => $sale->transaction_number,
            'transaction_number' => $sale->transaction_number,
            'header' => [
                'store_name' => $tenant?->name ?? 'Store',
                'address'    => $tenant?->address ?? null,
                'phone'      => $tenant?->phone ?? null,
                'cashier'    => $sale->user?->name,
                'date'       => $sale->created_at->toIso8601String(),
            ],
            'items'       => $lines,
            'subtotal'    => (float) $sale->subtotal,
            'discount'    => (float) $sale->discount_amount,
            'tax'         => (float) $sale->tax_amount,
            'total'       => (float) $sale->total,
            'amount_paid' => (float) $sale->amount_paid,
            'change'      => (float) $sale->change_amount,
            'payments'    => $payments,
            'status'      => $sale->status,
            'footer'      => [
                'message' => 'Thank you for your purchase!',
            ],
        ];
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    private function deductStock(Product $product, int $quantity, int $saleId, int $userId, int $tenantId): void
    {
        $before = (int) ($product->stock_quantity ?? 0);
        $after  = $before - $quantity;

        $product->update(['stock_quantity' => $after]);

        InventoryLog::create([
            'tenant_id'       => $tenantId,
            'product_id'      => $product->id,
            'user_id'         => $userId,
            'type'            => 'sale',
            'quantity_change' => -$quantity,
            'quantity_before' => $before,
            'quantity_after'  => $after,
            'reference_id'    => (string) $saleId,
            'reference_type'  => 'sale',
        ]);
    }

    private function persistPayments(Sale $sale, ?array $payments, string $fallbackMethod, float $fallbackAmount): void
    {
        if (is_array($payments) && count($payments) > 0) {
            foreach ($payments as $p) {
                SalePayment::create([
                    'sale_id'        => $sale->id,
                    'payment_method' => $p['payment_method'],
                    'amount'         => (float) $p['amount'],
                    'reference_no'   => $p['reference_no'] ?? null,
                    'paid_at'        => now(),
                ]);
            }
            return;
        }

        SalePayment::create([
            'sale_id'        => $sale->id,
            'payment_method' => $fallbackMethod ?: 'cash',
            'amount'         => $fallbackAmount,
            'reference_no'   => null,
            'paid_at'        => now(),
        ]);
    }

    private function ensurePaymentsCoverTotal(array $payments, float $total): void
    {
        $sum = array_sum(array_map(fn ($p) => (float) ($p['amount'] ?? 0), $payments));
        if (round($sum, 2) < round($total, 2)) {
            throw ValidationException::withMessages([
                'payments' => ["Sum of payments ({$sum}) is less than total ({$total})."],
            ]);
        }
    }
}
