<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Services\TokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;

/** Dùng chung cho customer/staff: cookie refresh token & body access token. */
trait Concerns
{
    protected function tokenResponse(TokenService $svc, array $t, int $status = 200, array $extra = []): JsonResponse
    {
        $res = response()->json(array_merge([
            'access_token' => $t['access_token'], 'token_type' => 'Bearer', 'expires_in' => $t['expires_in'],
        ], $extra), $status);
        if (! empty($t['refresh_token'])) {
            $res->headers->setCookie($this->refreshCookie($svc, $t['refresh_token'], $t['refresh_ttl']));
        }

        return $res;
    }

    protected function refreshCookie(TokenService $svc, string $value, int $ttl): Cookie
    {
        return new Cookie($svc->cfg('cookie'), $value, time() + $ttl, $svc->cfg('cookie_path'), null,
            config('shoe.auth.cookie_secure'), true, false, Cookie::SAMESITE_STRICT);
    }

    protected function clearCookie(TokenService $svc, JsonResponse $res): JsonResponse
    {
        $res->headers->setCookie(new Cookie($svc->cfg('cookie'), '', 1, $svc->cfg('cookie_path'), null,
            config('shoe.auth.cookie_secure'), true, false, Cookie::SAMESITE_STRICT));

        return $res;
    }

    protected function device(Request $r): array
    {
        return ['ip' => $r->ip(), 'user_agent' => substr((string) $r->userAgent(), 0, 200), 'device' => substr((string) $r->userAgent(), 0, 60)];
    }
}
