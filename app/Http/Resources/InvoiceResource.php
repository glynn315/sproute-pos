<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'invoice_number'     => $this->invoice_number,
            'amount'             => (float) $this->amount,
            'billing_cycle'      => $this->billing_cycle,
            'status'             => $this->status,
            'payment_method'     => $this->payment_method,
            'gcash_number'       => $this->gcash_number,
            'reference_number'   => $this->reference_number,
            'due_at'             => $this->due_at?->toIso8601String(),
            'paid_at'            => $this->paid_at?->toIso8601String(),
            'notes'              => $this->notes,
            'subscription_plan'  => new SubscriptionPlanResource($this->whenLoaded('subscriptionPlan')),
            'created_at'         => $this->created_at?->toIso8601String(),
            'updated_at'         => $this->updated_at?->toIso8601String(),

            // Snapshot of the merchant payment info so the frontend can render
            // the QR/instructions without a separate config endpoint.
            'merchant'           => [
                'gcash_number' => config('billing.gcash.number'),
                'gcash_name'   => config('billing.gcash.name'),
                'qr_path'      => config('billing.gcash.qr_path'),
                'instructions' => config('billing.invoice.instructions', []),
            ],
        ];
    }
}
