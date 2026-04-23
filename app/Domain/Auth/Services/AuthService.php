<?php

namespace App\Domain\Auth\Services;

use App\Domain\Auth\DTOs\LoginDTO;
use App\Domain\Auth\DTOs\PinLoginDTO;
use App\Domain\Auth\DTOs\RegisterTenantDTO;
use App\Models\RefreshToken;
use App\Models\Tenant;
use App\Models\TenantVerification;
use App\Models\User;
use App\Traits\AuditLogger;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\UnauthorizedException;

class AuthService
{
    use AuditLogger;

    private const REFRESH_TOKEN_TTL_DAYS    = 30;
    private const VERIFICATION_TOKEN_TTL_H  = 24;

    // ─── Registration ─────────────────────────────────────────────────────────

    public function registerTenant(RegisterTenantDTO $dto): array
    {
        return DB::transaction(function () use ($dto) {
            $tenant = Tenant::create([
                'name'   => $dto->storeName,
                'email'  => $dto->email,
                'phone'  => $dto->phone,
                'address'=> $dto->address,
                'status' => 'pending',
            ]);

            $owner = User::create([
                'tenant_id' => $tenant->id,
                'name'      => $dto->ownerName,
                'email'     => $dto->email,
                'password'  => $dto->password,
                'role'      => 'owner',
                'is_active' => true,
            ]);

            $verification = $this->createVerificationToken($tenant);

            $this->audit('registered', 'Tenant', $tenant->id, null, $tenant->toArray(), $tenant->id, $owner->id);

            return [
                'tenant'            => $tenant,
                'user'              => $owner,
                'verification_token'=> $verification->token,
            ];
        });
    }

    // ─── Email Verification ───────────────────────────────────────────────────

    public function verifyEmail(string $token): Tenant
    {
        $verification = TenantVerification::where('token', $token)->firstOrFail();

        if (! $verification->isValid()) {
            abort(422, 'Verification token is invalid or has expired.');
        }

        return DB::transaction(function () use ($verification) {
            $verification->update(['verified_at' => now()]);

            $tenant = $verification->tenant;
            $tenant->update(['status' => 'verified']);

            $owner = $tenant->owner;
            $owner?->update(['email_verified_at' => now()]);

            $this->audit('verified', 'Tenant', $tenant->id, null, null, $tenant->id, $owner?->id);

            return $tenant->fresh('subscriptionPlan');
        });
    }

    // ─── Login ────────────────────────────────────────────────────────────────

    public function login(LoginDTO $dto): array
    {
        $user = User::where('email', $dto->email)
            ->where('is_active', true)
            ->first();

        if (! $user || ! Hash::check($dto->password, $user->password)) {
            $this->audit('login_failed', 'User', null, ['email' => $dto->email]);
            throw new AuthenticationException('Invalid credentials.');
        }

        if ($user->tenant && ! $user->tenant->isVerified()) {
            throw new AuthenticationException('Your store account is pending verification.');
        }

        return $this->issueTokens($user, $dto->deviceName ?? 'api');
    }

    // ─── PIN Login ────────────────────────────────────────────────────────────

    public function pinLogin(PinLoginDTO $dto): array
    {
        $user = User::where('email', $dto->email)
            ->where('is_active', true)
            ->whereNotNull('pin')
            ->first();

        if (! $user || ! Hash::check($dto->pin, $user->pin)) {
            $this->audit('pin_login_failed', 'User', null, ['email' => $dto->email]);
            throw new AuthenticationException('Invalid PIN.');
        }

        if ($user->tenant && ! $user->tenant->isVerified()) {
            throw new AuthenticationException('Your store account is pending verification.');
        }

        return $this->issueTokens($user, $dto->deviceName ?? 'pin-device');
    }

    // ─── Refresh Token ────────────────────────────────────────────────────────

    public function refreshTokens(string $rawToken): array
    {
        $tokenHash  = hash('sha256', $rawToken);
        $refreshToken = RefreshToken::where('token_hash', $tokenHash)->with('user')->first();

        if (! $refreshToken || ! $refreshToken->isValid()) {
            throw new AuthenticationException('Refresh token is invalid or expired.');
        }

        $user = $refreshToken->user;

        $refreshToken->revoke();

        return $this->issueTokens($user, 'api');
    }

    // ─── Logout ───────────────────────────────────────────────────────────────

    public function logout(User $user, ?string $rawRefreshToken = null): void
    {
        // Revoke current Sanctum token
        $user->currentAccessToken()?->delete();

        // Revoke specific refresh token if provided
        if ($rawRefreshToken) {
            $tokenHash = hash('sha256', $rawRefreshToken);
            RefreshToken::where('token_hash', $tokenHash)->where('user_id', $user->id)
                ->update(['revoked_at' => now()]);
        }

        $user->update(['last_login_at' => $user->last_login_at]);

        $this->audit('logout', 'User', $user->id, null, null, $user->tenant_id, $user->id);
    }

    // ─── Password Reset ───────────────────────────────────────────────────────

    public function sendPasswordReset(string $email): void
    {
        $user = User::where('email', $email)->first();
        // Silently succeed even if user not found (prevent email enumeration)
        if ($user) {
            \Illuminate\Support\Facades\Password::sendResetLink(['email' => $email]);
        }
    }

    public function resetPassword(string $token, string $email, string $password): void
    {
        $status = \Illuminate\Support\Facades\Password::reset(
            ['email' => $email, 'password' => $password, 'password_confirmation' => $password, 'token' => $token],
            function (User $user, string $password) {
                $user->forceFill(['password' => $password])->save();
                $this->audit('password_reset', 'User', $user->id, null, null, $user->tenant_id, $user->id);
            }
        );

        if ($status !== \Illuminate\Support\Facades\Password::PASSWORD_RESET) {
            abort(422, __($status));
        }
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function issueTokens(User $user, string $deviceName): array
    {
        // Revoke old access tokens to enforce single session per device
        $user->tokens()->where('name', $deviceName)->delete();

        $accessToken = $user->createToken($deviceName, ['*'], now()->addHours(2));

        $rawRefresh  = Str::random(64);
        RefreshToken::create([
            'user_id'    => $user->id,
            'token_hash' => hash('sha256', $rawRefresh),
            'expires_at' => now()->addDays(self::REFRESH_TOKEN_TTL_DAYS),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        $user->update(['last_login_at' => now()]);

        $this->audit('login', 'User', $user->id, null, null, $user->tenant_id, $user->id);

        return [
            'access_token'  => $accessToken->plainTextToken,
            'refresh_token' => $rawRefresh,
            'token_type'    => 'Bearer',
            'expires_in'    => 7200,
            'user'          => $user->load('tenant.subscriptionPlan'),
        ];
    }

    private function createVerificationToken(Tenant $tenant): TenantVerification
    {
        // Invalidate old tokens
        TenantVerification::where('tenant_id', $tenant->id)
            ->whereNull('verified_at')
            ->delete();

        return TenantVerification::create([
            'tenant_id'  => $tenant->id,
            'token'      => Str::random(64),
            'expires_at' => now()->addHours(self::VERIFICATION_TOKEN_TTL_H),
        ]);
    }
}
