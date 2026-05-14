<?php

namespace App\Domain\Suppliers\DTOs;

use App\Http\Requests\Suppliers\UpdateSupplierRequest;

readonly class UpdateSupplierDTO
{
    public function __construct(
        public ?string $name,
        public ?string $contactName,
        public ?string $phone,
        public ?string $email,
        public ?string $address,
        public ?string $notes,
        public ?bool   $isActive,
    ) {}

    public static function fromRequest(UpdateSupplierRequest $request): self
    {
        return new self(
            name:        $request->validated('name'),
            contactName: $request->validated('contact_name'),
            phone:       $request->validated('phone'),
            email:       $request->validated('email'),
            address:     $request->validated('address'),
            notes:       $request->validated('notes'),
            isActive:    $request->validated('is_active'),
        );
    }
}
