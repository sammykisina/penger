<?php

namespace App\Http\Controllers\Api;

use App\Enums\Auth\OptTypes;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetOtpRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\VerifyRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\Response;
use JustSteveKing\StatusCode\Http;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    public function login(LoginRequest $request): Response
    {
        $user = $this->authService->login(
            data: $request->validated()
        );

        if ($user) {
            $token = $user->createToken(name: 'auth')->plainTextToken;

            return response(
                content: [
                    'user' => new UserResource($user),
                    'token' => $token,
                    'message' => $user->email_verified_at ? __('auth.login_success') : __('auth.login_success_verify'),
                ],
                status: Http::OK(),
            );
        }

        return response(
            content: [
                'message' => __('auth.failed'),
            ],
            status: Http::UNAUTHORIZED(),
        );
    }

    public function register(RegisterRequest $request): Response
    {
        $user = $this->authService->register(
            data: $request->validated()
        );

        $token = $user->createToken(name: 'auth')->plainTextToken;

        return response(
            content: [
                'user' => new UserResource($user),
                'token' => $token,
                'message' => __('auth.registration_success_verify'),
            ],
            status: Http::CREATED(),
        );
    }

    public function otp()
    {
        $user = auth()->user();

        $this->authService->opt(
            user: $user
        );

        return response(
            content: [
                'message' => __('auth.otp_send_success'),
            ],
            status: Http::CREATED(),
        );
    }

    public function verify(VerifyRequest $request): Response
    {
        $user = $this->authService->verify(
            user: auth()->user(),
            data: $request->validated()
        );

        return response(
            content: [
                'user' => new UserResource(
                    resource: $user
                ),
                'message' => __('auth.verification_success'),
            ],
            status: Http::OK(),
        );
    }

    public function resetOtp(ResetOtpRequest $request): Response
    {
        $user = $this->authService->getUserByEmail(
            email: $request->email
        );

        $this->authService->opt(
            user: $user,
            type: OptTypes::PASSWORD_RESET->value
        );

        return response(
            content: [
                'message' => __('auth.otp_send_success'),
            ],
            status: Http::OK(),
        );
    }

    public function resetPassword(ResetPasswordRequest $request): Response
    {
        $this->authService->resetPassword(
            user: $this->authService->getUserByEmail(
                email: $request->email
            ),
            data: $request->validated()
        );

        return response(
            content: [
                'message' => __('auth.password_reset_success'),
            ],
            status: Http::OK(),
        );
    }
}
