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

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', PasswordRule::rule()],
        ]);

        $user = User::query()->create([
            'name' => Text::clean($data['name']),
            'email' => Str::lower($data['email']),
            'password' => $data['password'],
            'is_staff' => false,
        ]);

        try {
            $user->sendEmailVerificationNotification();
        } catch (\Throwable $exception) {
            report($exception);
        }

        return $this->tokenResponse($user, 'Account created.', 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if ($limited = $this->limited($request, $data['email'], 'customer-login')) {
            return $limited;
        }

        $provider = Auth::createUserProvider('customers');
        $user = $provider->retrieveByCredentials(['email' => $data['email']]);

        if (! $user || ! $provider->validateCredentials($user, ['password' => $data['password']])) {
            RateLimiter::hit($this->key($request, $data['email'], 'customer-login'), 900);

            return ApiResponse::error('These credentials do not match our records.', 422);
        }

        RateLimiter::clear($this->key($request, $data['email'], 'customer-login'));

        return $this->tokenResponse($user, 'Logged in.');
    }

    public function me(Request $request): JsonResponse
    {
        return ApiResponse::success($request->user()->toPublicArray());
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return ApiResponse::success(null, 'Logged out.');
    }

    public function forgot(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email']]);

        if (RateLimiter::tooManyAttempts('password-email:'.$request->ip(), 5)) {
            return ApiResponse::error('Too many reset attempts. Try again later.', 429);
        }

        RateLimiter::hit('password-email:'.$request->ip(), 60);

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

    public function verifyEmail(Request $request, string $id, string $hash)
    {
        $user = User::query()->findOrFail($id);

        if (! hash_equals($hash, sha1($user->getEmailForVerification()))) {
            abort(403);
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return redirect()->away(rtrim(config('app.frontend_url'), '/').'/login?verified=1');
    }

    private function tokenResponse(User $user, string $message, int $status = 200): JsonResponse
    {
        $token = $user->createToken('customer', ['customer'])->plainTextToken;

        return ApiResponse::success([
            'token' => $token,
            'user' => $user->toPublicArray(),
        ], $message, $status);
    }

    private function limited(Request $request, string $email, string $prefix): ?JsonResponse
    {
        $key = $this->key($request, $email, $prefix);

        if (! RateLimiter::tooManyAttempts($key, 5)) {
            return null;
        }

        return ApiResponse::error(
            'Too many login attempts. Try again in '.RateLimiter::availableIn($key).' seconds.',
            429
        );
    }

    private function key(Request $request, string $email, string $prefix): string
    {
        return $prefix.':'.Str::lower($email).'|'.$request->ip();
    }
}
