<?php

namespace App\Domain\Sales\DTOs;

use App\Http\Requests\Sales\CreateSaleRequest;
use App\Models\User;

readonly class CreateSaleDTO
{
    public function __construct(
        public int     $tenantId,
        public int     $userId,
        public array   $items,
        public string  $paymentMethod,
        public float   $amountPaid,
        public float   $discountAmount,
        public float   $taxAmount,
        public ?string $notes,
    ) {}

    public static function fromRequest(CreateSaleRequest $request, User $user): self
    {
        return new self(
            tenantId:       $user->tenant_id,
            userId:         $user->id,
            items:          $request->validated('items'),
            paymentMethod:  $request->validated('payment_method', 'cash'),
            amountPaid:     (float) $request->validated('amount_paid'),
            discountAmount: (float) ($request->validated('discount_amount') ?? 0),
            taxAmount:      (float) ($request->validated('tax_amount') ?? 0),
            notes:          $request->validated('notes'),
        );
    }
}
