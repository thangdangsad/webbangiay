<?php

namespace App\Http\Controllers\Auth\Concerns;

use App\Services\Auth\TokenBroker;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Cookie as SymfonyCookie;

/**
 * Shared access/refresh-token plumbing for CustomerAuthController and
 * AdminAuthController. The access token is returned in the JSON body (the
 * SPA holds it in memory and sends it as `Authorization: Bearer <token>`);
 * the refresh token is set as an HttpOnly cookie scoped to the guard's own
 * refresh endpoint, so JS never sees it and it's never replayed elsewhere.
 */
trait IssuesAuthTokens
{
    private function tokens(): TokenBroker
    {
        return app(TokenBroker::class);
    }

    /**
     * Issue a fresh access + refresh token pair for $user and build the
     * JSON response (access token in body, refresh token as cookie).
     */
    private function tokenResponse(string $guard, Authenticatable $user, string $refreshPath, mixed $data, int $status = 200): JsonResponse
    {
        $access = $this->tokens()->issueAccessToken($guard, $user->getAuthIdentifier());
        $refresh = $this->tokens()->issueRefreshToken($guard, $user->getAuthIdentifier());

        return response()->json([
            'data' => $data,
            'access_token' => $access['token'],
            'expires_in' => $access['ttl'],
        ], $status)->withCookie($this->refreshCookie($guard, $refresh['token'], $refresh['ttl'], $refreshPath));
    }

    /**
     * Revoke both the current access token (from the Authorization header)
     * and the current refresh token (from its cookie), if present.
     */
    private function revokeTokens(Request $request, string $guard, string $refreshPath): JsonResponse
    {
        $accessToken = $request->bearerToken();

        if ($accessToken) {
            $this->tokens()->revokeAccessToken($accessToken);
        }

        $refreshToken = $request->cookie($this->refreshCookieName($guard));

        if ($refreshToken) {
            $this->tokens()->revokeRefreshToken($refreshToken);
        }

        return response()->json(null, 204)->withCookie(Cookie::forget($this->refreshCookieName($guard), $refreshPath));
    }

    /**
     * Validate the refresh cookie, rotate it (single-use), and issue a
     * fresh access + refresh pair for the same user.
     */
    private function handleRefresh(Request $request, string $guard, string $refreshPath, string $modelClass, mixed $dataCallback): JsonResponse
    {
        $refreshToken = $request->cookie($this->refreshCookieName($guard));

        abort_if(! $refreshToken, 401, 'Refresh token không hợp lệ hoặc đã hết hạn.');

        $payload = $this->tokens()->resolveRefreshToken($refreshToken);

        abort_if(! $payload || $payload['guard'] !== $guard, 401, 'Refresh token không hợp lệ hoặc đã hết hạn.');

        // Single-use rotation: the old refresh token can't be replayed.
        $this->tokens()->revokeRefreshToken($refreshToken);

        $user = $modelClass::find($payload['user_id']);

        abort_if(! $user, 401, 'Refresh token không hợp lệ hoặc đã hết hạn.');

        return $this->tokenResponse($guard, $user, $refreshPath, $dataCallback($user));
    }

    private function refreshCookie(string $guard, string $value, int $ttlSeconds, string $path): SymfonyCookie
    {
        return Cookie::make(
            name: $this->refreshCookieName($guard),
            value: $value,
            minutes: (int) ceil($ttlSeconds / 60),
            path: $path,
            domain: null,
            secure: (bool) config('token_auth.cookie_secure'),
            httpOnly: true,
            raw: false,
            sameSite: config('token_auth.cookie_same_site'),
        );
    }

    private function refreshCookieName(string $guard): string
    {
        return "{$guard}_refresh_token";
    }
}
