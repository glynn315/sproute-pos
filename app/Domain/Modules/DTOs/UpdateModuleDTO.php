<?php

namespace App\Domain\Modules\DTOs;

use App\Http\Requests\Admin\Modules\UpdateModuleRequest;

readonly class UpdateModuleDTO
{
    public function __construct(
        public ?string $displayName,
        public ?string $description,
        public ?string $icon,
        public ?bool   $isActive,
        public ?int    $sortOrder,
    ) {}

    public static function fromRequest(UpdateModuleRequest $request): self
    {
        $v = $request->validated();
        return new self(
            displayName: $v['display_name'] ?? null,
            description: array_key_exists('description', $v) ? $v['description'] : null,
            icon:        array_key_exists('icon', $v) ? $v['icon'] : null,
            isActive:    array_key_exists('is_active', $v) ? (bool) $v['is_active'] : null,
            sortOrder:   isset($v['sort_order']) ? (int) $v['sort_order'] : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'display_name' => $this->displayName,
            'description'  => $this->description,
            'icon'         => $this->icon,
            'is_active'    => $this->isActive,
            'sort_order'   => $this->sortOrder,
        ], static fn ($v) => $v !== null);
    }
}
