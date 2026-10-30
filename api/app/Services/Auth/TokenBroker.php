<?php

namespace App\Services\Auth;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Issues and resolves the opaque access/refresh tokens used by the
 * customer/admin "redis-token" guards (see AuthServiceProvider). Both token
 * kinds live only in Redis (via the dedicated "redis" cache store), keyed by
 * the token itself — there is no database table backing them.
 */
class TokenBroker
{
    /**
     * @return array{token: string, ttl: int}
     */
    public function issueAccessToken(string $guard, int|string $userId): array
    {
        return $this->issue('access', $guard, $userId, (int) config('token_auth.access_ttl'));
    }

    /**
     * @return array{token: string, ttl: int}
     */
    public function issueRefreshToken(string $guard, int|string $userId): array
    {
        return $this->issue('refresh', $guard, $userId, (int) config('token_auth.refresh_ttl'));
    }

    /**
     * @return array{guard: string, user_id: int|string}|null
     */
    public function resolveAccessToken(string $token): ?array
    {
        return $this->store()->get($this->key('access', $token));
    }

    /**
     * @return array{guard: string, user_id: int|string}|null
     */
    public function resolveRefreshToken(string $token): ?array
    {
        return $this->store()->get($this->key('refresh', $token));
    }

    public function revokeAccessToken(string $token): void
    {
        $this->store()->forget($this->key('access', $token));
    }

    public function revokeRefreshToken(string $token): void
    {
        $this->store()->forget($this->key('refresh', $token));
    }

    /**
     * @return array{token: string, ttl: int}
     */
    private function issue(string $type, string $guard, int|string $userId, int $ttlSeconds): array
    {
        $token = Str::random(64);

        $this->store()->put($this->key($type, $token), [
            'guard' => $guard,
            'user_id' => $userId,
        ], $ttlSeconds);

        return ['token' => $token, 'ttl' => $ttlSeconds];
    }

    private function key(string $type, string $token): string
    {
        return "auth_tokens:{$type}:{$token}";
    }

    private function store(): Repository
    {
        return Cache::store(config('token_auth.store'));
    }
}
