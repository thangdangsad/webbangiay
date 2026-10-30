<?php

namespace App\Providers;

use App\Models\CustomerAccount;
use App\Models\Staff;
use App\Services\Auth\TokenBroker;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

/**
 * Registers the "redis-token:customer" and "redis-token:admin" guard
 * drivers used by config/auth.php. Each resolves the current user from the
 * `Authorization: Bearer <access token>` header by looking the token up in
 * Redis (via TokenBroker) — no session, no database-backed token table.
 */
class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Auth::viaRequest('redis-token:customer', fn (Request $request) => $this->resolveFromToken($request, 'customer', CustomerAccount::class));

        Auth::viaRequest('redis-token:admin', fn (Request $request) => $this->resolveFromToken($request, 'admin', Staff::class));
    }

    /**
     * @param  class-string<Authenticatable>  $model
     */
    private function resolveFromToken(Request $request, string $guard, string $model): ?Authenticatable
    {
        $token = $request->bearerToken();

        if (! $token) {
            return null;
        }

        $payload = app(TokenBroker::class)->resolveAccessToken($token);

        if (! $payload || $payload['guard'] !== $guard) {
            return null;
        }

        return $model::find($payload['user_id']);
    }
}
