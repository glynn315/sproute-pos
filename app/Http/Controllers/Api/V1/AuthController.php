<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Auth\DTOs\LoginDTO;
use App\Domain\Auth\DTOs\PinLoginDTO;
use App\Domain\Auth\DTOs\RegisterTenantDTO;
use App\Domain\Auth\Services\AuthService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\PinLoginRequest;
use App\Http\Requests\Auth\RefreshTokenRequest;
use App\Http\Requests\Auth\RegisterTenantRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\TenantResource;
use App\Http\Resources\UserResource;
use App\Traits\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly AuthService $authService) {}

    public function register(RegisterTenantRequest $request): JsonResponse
    {
        $result = $this->authService->registerTenant(RegisterTenantDTO::fromRequest($request));

        return $this->created([
            'tenant'  => new TenantResource($result['tenant']),
            'message' => 'Registration successful. A verification link has been sent to your email.',
        ], 'Registration successful');
    }

    public function verifyEmail(string $token): JsonResponse
    {
        $tenant = $this->authService->verifyEmail($token);

        return $this->success(
            new TenantResource($tenant),
            'Email verified successfully. You can now log in.'
        );
    }

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login(LoginDTO::fromRequest($request));

            return $this->success([
                'access_token'  => $result['access_token'],
                'refresh_token' => $result['refresh_token'],
                'token_type'    => $result['token_type'],
                'expires_in'    => $result['expires_in'],
                'user'          => new UserResource($result['user']),
            ], 'Login successful');
        } catch (AuthenticationException $e) {
            return $this->unauthorized($e->getMessage());
        }
    }

    public function pinLogin(PinLoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->pinLogin(PinLoginDTO::fromRequest($request));

            return $this->success([
                'access_token'  => $result['access_token'],
                'refresh_token' => $result['refresh_token'],
                'token_type'    => $result['token_type'],
                'expires_in'    => $result['expires_in'],
                'user'          => new UserResource($result['user']),
            ], 'PIN login successful');
        } catch (AuthenticationException $e) {
            return $this->unauthorized($e->getMessage());
        }
    }

    public function refresh(RefreshTokenRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->refreshTokens($request->validated('refresh_token'));

            return $this->success([
                'access_token'  => $result['access_token'],
                'refresh_token' => $result['refresh_token'],
                'token_type'    => $result['token_type'],
                'expires_in'    => $result['expires_in'],
            ], 'Token refreshed');
        } catch (AuthenticationException $e) {
            return $this->unauthorized($e->getMessage());
        }
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout(
            $request->user(),
            $request->input('refresh_token')
        );

        return $this->success(null, 'Logged out successfully');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->success(
            new UserResource($request->user()->load('tenant.subscriptionPlan'))
        );
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->authService->sendPasswordReset($request->validated('email'));

        return $this->success(null, 'If that email is registered, a password reset link has been sent.');
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $this->authService->resetPassword(
            $request->validated('token'),
            $request->validated('email'),
            $request->validated('password')
        );

        return $this->success(null, 'Password reset successfully. You can now log in.');
    }
}
