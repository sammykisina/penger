<?php

namespace App\Services;

use App\Enums\Auth\OptTypes;
use App\Mail\OtpMail;
use App\Models\Otp;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use JustSteveKing\StatusCode\Http;

class AuthService
{
    public function register(array $data): User
    {
        $user = User::create([
            'uuid' => Str::uuid(),
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $this->opt(user: $user);

        return $user;
    }

    public function login(array $data): ?User
    {
        $user = User::query()->where('email', $data['email'])->first();

        if ($user && Hash::check(value: $data['password'], hashedValue: $user->password)) {
            return $user;
        }

        return null;
    }

    public function opt(User $user, string $type = OptTypes::VERIFICATION->value): void
    {
        $tries = 3;
        $time = Carbon::now()->subMinutes(30); // 30 mins in the past
        $count = $otp = Otp::query()->where([
            'user_id' => $user->id,
            'active' => true,
            'type' => $type,
        ])->where('created_at', '>=', $time)->count();

        if ($count >= $tries) {
            abort(Http::UNPROCESSABLE_ENTITY(), __('auth.otp_tries_exceeded'));
        }

        $code = random_int(min: 100000, max: 999999);

        $otp = Otp::create([
            'type' => $type,
            'code' => $code,
            'active' => true,
            'user_id' => $user->id,
        ]);

        Mail::to($user)->send(
            new OtpMail(
                user: $user,
                otp: $otp
            )
        );
    }

    public function verify(User $user, array $data): User
    {
        $otp = Otp::query()->where([
            'code' => $data['otp'],
            'user_id' => $user->id,
            'active' => true,
            'type' => OptTypes::VERIFICATION->value,
        ])->first();

        if (! $otp) {
            abort(Http::UNPROCESSABLE_ENTITY(), __('auth.invalid_otp'));
        }

        $user->email_verified_at = Carbon::now();
        $user->update();

        $otp->update([
            'active' => false,
            'updated_at' => Carbon::now(),
        ]);

        return $user;
    }

    public function getUserByEmail(string $email): User
    {
        return User::query()->where('email', $email)->first();
    }

    public function resetPassword(User $user, array $data): User
    {
        $otp = Otp::query()->where([
            'code' => $data['otp'],
            'user_id' => $user->id,
            'active' => true,
            'type' => OptTypes::PASSWORD_RESET->value,
        ])->first();

        if (! $otp) {
            abort(Http::UNPROCESSABLE_ENTITY(), __('auth.invalid_otp'));
        }

        $user->password = Hash::make($data['password']);
        $user->updated_at = Carbon::now();
        $user->update();

        $otp->update([
            'active' => false,
            'updated_at' => Carbon::now(),
        ]);

        return $user;
    }
}
