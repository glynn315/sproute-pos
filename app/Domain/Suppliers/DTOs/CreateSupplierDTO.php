<?php

namespace App\Domain\Suppliers\DTOs;

use App\Http\Requests\Suppliers\CreateSupplierRequest;

readonly class CreateSupplierDTO
{
    public function __construct(
        public int     $tenantId,
        public string  $name,
        public ?string $contactName,
        public ?string $phone,
        public ?string $email,
        public ?string $address,
        public ?string $notes,
    ) {}

    public static function fromRequest(CreateSupplierRequest $request): self
    {
        return new self(
            tenantId:    auth()->user()->tenant_id,
            name:        $request->validated('name'),
            contactName: $request->validated('contact_name'),
            phone:       $request->validated('phone'),
            email:       $request->validated('email'),
            address:     $request->validated('address'),
            notes:       $request->validated('notes'),
        );
    }
}
