<?php

namespace App\Domain\Eatery\Tables\DTOs;

use App\Http\Requests\Eatery\Tables\StoreRestaurantTableRequest;
use App\Models\User;

readonly class StoreRestaurantTableDTO
{
    public function __construct(
        public int     $tenantId,
        public int     $tableNumber,
        public ?string $label,
        public int     $seats,
        public ?string $notes,
    ) {}

    public static function fromRequest(StoreRestaurantTableRequest $request, User $user): self
    {
        return new self(
            tenantId:    $user->tenant_id,
            tableNumber: (int) $request->validated('table_number'),
            label:       $request->validated('label'),
            seats:       (int) ($request->validated('seats') ?? 4),
            notes:       $request->validated('notes'),
        );
    }
}
