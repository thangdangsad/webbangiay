<?php

namespace App\Modules\Auth;

use App\Modules\Auth\Services\TokenService;
use App\Modules\User\Models\StaffUser;
use App\Modules\User\Models\User;
use App\Support\ApiException;
use Illuminate\Auth\RequestGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

/** Đăng ký guard `jwt` cho `customer` và `staff` (aud = tên guard). */
class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Auth::extend('jwt', function ($app, $name, array $config) {
            $guard = new RequestGuard(function (Request $request) use ($name) {
                $token = $request->bearerToken();
                if (! $token) {
                    return null;
                }
                $svc = TokenService::for($name);
                $claims = $svc->authenticate($token);
                $model = $name === 'staff' ? StaffUser::class : User::class;
                $user = $model::find($claims['sub']);
                if (! $user || $user->status !== 'active') {
                    throw ApiException::unauthorized('SESSION_REVOKED', 'Tài khoản không khả dụng');
                }
                $request->attributes->set("jwt.$name", $claims);

                return $user;
            }, $app['request'], $app['auth']->createUserProvider($config['provider'] ?? null));
            $app->refresh('request', $guard, 'setRequest'); // request mới (Octane/test) → guard luôn đọc đúng request
            $app->rebinding('request', fn () => $guard->forgetUser());

            return $guard;
        });
    }
}
