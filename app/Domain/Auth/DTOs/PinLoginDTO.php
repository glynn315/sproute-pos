<?php

namespace App\Domain\Auth\DTOs;

use App\Http\Requests\Auth\PinLoginRequest;

readonly class PinLoginDTO
{
    public function __construct(
        public string  $email,
        public string  $pin,
        public ?string $deviceName,
    ) {}

    public static function fromRequest(PinLoginRequest $request): self
    {
        return new self(
            email:      $request->validated('email'),
            pin:        $request->validated('pin'),
            deviceName: $request->validated('device_name'),
        );
    }
}
