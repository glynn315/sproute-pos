<?php

namespace App\Domain\Eatery\Payments\DTOs;

use App\Http\Requests\Eatery\Payments\CreatePaymentRequest;
use App\Models\User;

readonly class CreatePaymentDTO
{
    public function __construct(
        public int     $tenantId,
        public int     $userId,
        public ?int    $orderId,
        public ?int    $restaurantTableId,
        public float   $cashReceived,
        public string  $paymentMethod,
        public ?string $reference,
        public ?string $notes,
    ) {}

    public static function fromRequest(CreatePaymentRequest $request, User $user): self
    {
        return new self(
            tenantId:          $user->tenant_id,
            userId:            $user->id,
            orderId:           $request->validated('order_id'),
            restaurantTableId: $request->validated('table_id'),
            cashReceived:      (float) $request->validated('cash_received'),
            paymentMethod:     $request->validated('payment_method', 'cash'),
            reference:         $request->validated('reference'),
            notes:             $request->validated('notes'),
        );
    }
}
