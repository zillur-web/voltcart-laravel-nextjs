<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ApiResponse;
use App\Support\PasswordRule;
use App\Support\Text;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AdminAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $key = 'admin-login:'.Str::lower($data['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return ApiResponse::error(
                'Too many login attempts. Try again in '.RateLimiter::availableIn($key).' seconds.',
                429
            );
        }

        $provider = Auth::createUserProvider('staff');
        $user = $provider->retrieveByCredentials(['email' => $data['email']]);

        if (! $user || ! $provider->validateCredentials($user, ['password' => $data['password']])) {
            RateLimiter::hit($key, 900);

            return ApiResponse::error('These credentials do not match our records.', 422);
        }

        RateLimiter::clear($key);
        $token = $user->createToken('admin', ['admin'])->plainTextToken;

        return ApiResponse::success([
            'token' => $token,
            'user' => $user->toStaffArray(),
        ], 'Logged in.');
    }

    public function me(Request $request): JsonResponse
    {
        return ApiResponse::success($request->user()->toStaffArray());
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return ApiResponse::success(null, 'Logged out.');
    }

    public function forgot(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email']]);

        try {
            Password::sendResetLink(['email' => Str::lower($data['email'])]);
        } catch (\Throwable $exception) {
            report($exception);
        }

        return ApiResponse::success(null, 'If an account exists for that email, a reset link has been sent.');
    }

    public function reset(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::rule()],
        ]);

        $status = Password::reset(
            [
                'email' => Str::lower($data['email']),
                'password' => $data['password'],
                'password_confirmation' => $request->input('password_confirmation'),
                'token' => $data['token'],
            ],
            function (User $user, string $password) {
                if (! $user->is_staff) {
                    return;
                }
                $user->forceFill(['password' => Hash::make($password)])->save();
                $user->tokens()->delete();
                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return ApiResponse::error('This password reset link is invalid or has expired.', 422);
        }

        return ApiResponse::success(null, 'Password updated. You can sign in now.');
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'current_password' => ['required_with:password', 'string'],
            'password' => ['nullable', 'confirmed', PasswordRule::rule()],
        ]);

        $user->name = Text::clean($data['name']);

        if (! empty($data['password'])) {
            if (! Hash::check($data['current_password'] ?? '', $user->password)) {
                return ApiResponse::error('Current password is incorrect.', 422, [
                    'current_password' => ['Current password is incorrect.'],
                ]);
            }
            $user->password = $data['password'];
        }

        $user->save();

        return ApiResponse::success($user->toStaffArray(), 'Profile updated.');
    }
}
