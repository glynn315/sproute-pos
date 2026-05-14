<?php

namespace App\Domain\Eatery\Orders\DTOs;

use App\Http\Requests\Eatery\Orders\AddOrderItemsRequest;

readonly class AddOrderItemsDTO
{
    public function __construct(
        public array $items,
    ) {}

    public static function fromRequest(AddOrderItemsRequest $request): self
    {
        return new self(items: $request->validated('items'));
    }
}
