<?php

namespace App\Domain\Eatery\Menu\DTOs;

use App\Http\Requests\Eatery\Menu\StoreMenuItemRequest;
use App\Models\User;

readonly class StoreMenuItemDTO
{
    public function __construct(
        public int     $tenantId,
        public ?int    $categoryId,
        public string  $name,
        public ?string $description,
        public ?string $category,
        public float   $price,
        public ?string $imageUrl,
        public bool    $availability,
    ) {}

    public static function fromRequest(StoreMenuItemRequest $request, User $user): self
    {
        return new self(
            tenantId:     $user->tenant_id,
            categoryId:   $request->validated('category_id'),
            name:         $request->validated('name'),
            description:  $request->validated('description'),
            category:     $request->validated('category'),
            price:        (float) $request->validated('price'),
            imageUrl:     $request->validated('image_url'),
            availability: (bool) ($request->validated('availability') ?? true),
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id'    => $this->tenantId,
            'category_id'  => $this->categoryId,
            'name'         => $this->name,
            'description'  => $this->description,
            'category'     => $this->category,
            'price'        => $this->price,
            'image_url'    => $this->imageUrl,
            'availability' => $this->availability,
        ];
    }
}
