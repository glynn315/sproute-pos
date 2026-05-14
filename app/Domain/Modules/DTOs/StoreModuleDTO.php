<?php

namespace App\Domain\Modules\DTOs;

use App\Http\Requests\Admin\Modules\StoreModuleRequest;

readonly class StoreModuleDTO
{
    public function __construct(
        public string  $name,
        public string  $displayName,
        public ?string $description,
        public ?string $icon,
        public bool    $isActive,
        public int     $sortOrder,
    ) {}

    public static function fromRequest(StoreModuleRequest $request): self
    {
        return new self(
            name:        strtolower(trim($request->validated('name'))),
            displayName: $request->validated('display_name'),
            description: $request->validated('description'),
            icon:        $request->validated('icon'),
            isActive:    (bool) ($request->validated('is_active') ?? true),
            sortOrder:   (int)  ($request->validated('sort_order') ?? 0),
        );
    }

    public function toArray(): array
    {
        return [
            'name'         => $this->name,
            'display_name' => $this->displayName,
            'description'  => $this->description,
            'icon'         => $this->icon,
            'is_active'    => $this->isActive,
            'sort_order'   => $this->sortOrder,
        ];
    }
}
