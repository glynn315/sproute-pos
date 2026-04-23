<?php

namespace App\Domain\Employees\DTOs;

use App\Http\Requests\Employees\UpdateEmployeeRequest;

readonly class UpdateEmployeeDTO
{
    public function __construct(
        public ?string $name,
        public ?string $email,
        public ?string $password,
        public ?string $pin,
        public ?string $role,
        public ?bool   $isActive,
    ) {}

    public static function fromRequest(UpdateEmployeeRequest $request): self
    {
        return new self(
            name:     $request->validated('name'),
            email:    $request->validated('email'),
            password: $request->validated('password'),
            pin:      $request->validated('pin'),
            role:     $request->validated('role'),
            isActive: $request->validated('is_active'),
        );
    }
}
