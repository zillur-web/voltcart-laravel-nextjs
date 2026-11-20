<?php

namespace App\Providers;

use App\Auth\CustomerUserProvider;
use App\Auth\StaffUserProvider;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Auth::provider('staff_eloquent', function ($app, array $config) {
            return new StaffUserProvider($app['hash'], $config['model']);
        });

        Auth::provider('customer_eloquent', function ($app, array $config) {
            return new CustomerUserProvider($app['hash'], $config['model']);
        });

        ResetPassword::createUrlUsing(function (User $user, string $token) {
            $base = rtrim((string) config('app.frontend_url'), '/');
            $path = $user->is_staff ? '/admin/reset-password' : '/reset-password';

            return $base.$path.'?token='.$token.'&email='.urlencode($user->email);
        });

        VerifyEmail::createUrlUsing(function (User $user) {
            return URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes(60),
                [
                    'id' => $user->getKey(),
                    'hash' => sha1($user->getEmailForVerification()),
                ]
            );
        });
    }
}
