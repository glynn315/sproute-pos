<?php

namespace App\Domain\Tenants\DTOs;

use App\Http\Requests\Tenants\UpdateTenantRequest;

readonly class UpdateTenantDTO
{
    public function __construct(
        public ?string $name,
        public ?string $phone,
        public ?string $address,
        public ?string $logoUrl,
        public ?string $primaryColor,
        public ?string $secondaryColor,
    ) {}

    public static function fromRequest(UpdateTenantRequest $request): self
    {
        return new self(
            name:           $request->validated('name'),
            phone:          $request->validated('phone'),
            address:        $request->validated('address'),
            logoUrl:        $request->validated('logo_url'),
            primaryColor:   $request->validated('primary_color'),
            secondaryColor: $request->validated('secondary_color'),
        );
    }
}
