<?php

namespace App\Domain\Eatery\Tables\DTOs;

use App\Http\Requests\Eatery\Tables\UpdateRestaurantTableRequest;

readonly class UpdateRestaurantTableDTO
{
    public function __construct(
        public ?int    $tableNumber,
        public ?string $label,
        public ?int    $seats,
        public ?string $status,
        public ?string $notes,
    ) {}

    public static function fromRequest(UpdateRestaurantTableRequest $request): self
    {
        $v = $request->validated();
        return new self(
            tableNumber: isset($v['table_number']) ? (int) $v['table_number'] : null,
            label:       $v['label']  ?? null,
            seats:       isset($v['seats']) ? (int) $v['seats'] : null,
            status:      $v['status'] ?? null,
            notes:       $v['notes']  ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'table_number' => $this->tableNumber,
            'label'        => $this->label,
            'seats'        => $this->seats,
            'status'       => $this->status,
            'notes'        => $this->notes,
        ], static fn ($v) => $v !== null);
    }
}
