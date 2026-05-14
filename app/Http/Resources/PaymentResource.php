<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'payment_id'     => $this->payment_id,
            'order_id'       => $this->order_id,
            'order'          => $this->whenLoaded('order', fn () => new OrderResource($this->order)),
            'cashier'        => $this->whenLoaded('cashier', fn () => $this->cashier ? [
                'id'   => $this->cashier->id,
                'name' => $this->cashier->name,
            ] : null),
            'total_amount'   => (float) $this->total_amount,
            'cash_received'  => (float) $this->cash_received,
            'change_amount'  => (float) $this->change_amount,
            'payment_method' => $this->payment_method,
            'payment_date'   => optional($this->payment_date)->toIso8601String(),
            'reference'      => $this->reference,
            'notes'          => $this->notes,
            'receipt'        => $this->receipt(),
        ];
    }

    private function receipt(): array
    {
        $order = $this->whenLoaded('order') ? $this->order : null;
        $table = $order && $order->relationLoaded('table') ? $order->table : null;
        $items = $order && $order->relationLoaded('items') ? $order->items : collect();

        $lines = [
            'SPROUTE POS',
            '----------------------------',
            'Receipt #: R-' . $this->payment_id,
            'Order #:   ' . ($order->order_number ?? '-'),
            'Table:     ' . ($table->table_number ?? '-'),
            'Date:      ' . optional($this->payment_date)->format('Y-m-d H:i'),
            '----------------------------',
        ];
        foreach ($items as $it) {
            $lines[] = sprintf(
                '%dx %-18s %8.2f',
                $it->quantity,
                substr($it->item_name, 0, 18),
                (float) $it->subtotal,
            );
        }
        $lines[] = '----------------------------';
        $lines[] = sprintf('TOTAL:        %8.2f', (float) $this->total_amount);
        $lines[] = sprintf('CASH:         %8.2f', (float) $this->cash_received);
        $lines[] = sprintf('CHANGE:       %8.2f', (float) $this->change_amount);
        $lines[] = '----------------------------';
        $lines[] = 'Thank you, come again!';

        return [
            'receipt_no'   => 'R-' . $this->payment_id,
            'print_lines'  => $lines,
        ];
    }
}
