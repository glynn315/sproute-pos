<?php

namespace App\Domain\Categories\DTOs;

use App\Http\Requests\Categories\CreateCategoryRequest;

readonly class CreateCategoryDTO
{
    public function __construct(
        public int     $tenantId,
        public string  $name,
        public ?string $description,
    ) {}

    public static function fromRequest(CreateCategoryRequest $request): self
    {
        return new self(
            tenantId:    auth()->user()->tenant_id,
            name:        $request->validated('name'),
            description: $request->validated('description'),
        );
    }
}
