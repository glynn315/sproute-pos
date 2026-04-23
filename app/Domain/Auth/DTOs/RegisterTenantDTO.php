<?php

namespace App\Domain\Auth\DTOs;

use App\Http\Requests\Auth\RegisterTenantRequest;

readonly class RegisterTenantDTO
{
    public function __construct(
        public string  $storeName,
        public string  $ownerName,
        public string  $email,
        public string  $password,
        public ?string $phone,
        public ?string $address,
    ) {}

    public static function fromRequest(RegisterTenantRequest $request): self
    {
        return new self(
            storeName: $request->validated('store_name'),
            ownerName: $request->validated('owner_name'),
            email:     $request->validated('email'),
            password:  $request->validated('password'),
            phone:     $request->validated('phone'),
            address:   $request->validated('address'),
        );
    }
}
