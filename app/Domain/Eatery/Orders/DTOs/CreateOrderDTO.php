<?php

namespace App\Domain\Eatery\Orders\DTOs;

use App\Http\Requests\Eatery\Orders\CreateOrderRequest;
use App\Models\User;

readonly class CreateOrderDTO
{
    public function __construct(
        public int     $tenantId,
        public int     $userId,
        public int     $restaurantTableId,
        public array   $items,
        public ?string $notes,
    ) {}

    public static function fromRequest(CreateOrderRequest $request, User $user): self
    {
        return new self(
            tenantId:          $user->tenant_id,
            userId:            $user->id,
            restaurantTableId: (int) $request->validated('restaurant_table_id'),
            items:             $request->validated('items'),
            notes:             $request->validated('notes'),
        );
    }
}
