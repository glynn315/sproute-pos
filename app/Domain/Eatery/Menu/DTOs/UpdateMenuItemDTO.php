<?php

namespace App\Domain\Eatery\Menu\DTOs;

use App\Http\Requests\Eatery\Menu\UpdateMenuItemRequest;

readonly class UpdateMenuItemDTO
{
    public function __construct(
        public ?int    $categoryId,
        public ?string $name,
        public ?string $description,
        public ?string $category,
        public ?float  $price,
        public ?string $imageUrl,
        public ?bool   $availability,
    ) {}

    public static function fromRequest(UpdateMenuItemRequest $request): self
    {
        $v = $request->validated();
        return new self(
            categoryId:   array_key_exists('category_id', $v) ? $v['category_id'] : null,
            name:         $v['name']        ?? null,
            description:  $v['description'] ?? null,
            category:     $v['category']    ?? null,
            price:        isset($v['price']) ? (float) $v['price'] : null,
            imageUrl:     $v['image_url']   ?? null,
            availability: array_key_exists('availability', $v) ? (bool) $v['availability'] : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'category_id'  => $this->categoryId,
            'name'         => $this->name,
            'description'  => $this->description,
            'category'     => $this->category,
            'price'        => $this->price,
            'image_url'    => $this->imageUrl,
            'availability' => $this->availability,
        ], static fn ($v) => $v !== null);
    }
}
