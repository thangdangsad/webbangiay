<?php

namespace Tests\Concerns;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Testing\TestResponse;

/**
 * Shared helpers for feature tests simulating real requests from the
 * storefront/admin-panel SPAs: a Referer header matching a configured
 * Sanctum stateful domain is required for EnsureFrontendRequestsAreStateful
 * to turn on session/cookie auth at all (see config/sanctum.php `stateful`
 * + config/cors.php). CSRF is disabled because it's Sanctum/Laravel's own
 * well-tested mechanism, not app logic under test here.
 */
trait InteractsWithStatefulSession
{
    protected function actingAsStatefulSpa(): void
    {
        $this->withHeader('Referer', 'http://localhost:5180/');
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    /**
     * Carries the session cookie set by $response into the next request
     * made through $this, simulating the browser echoing the cookie back.
     * This is only the guest-cart session (see App\Services\CartResolver) —
     * customer/admin auth is Bearer-token based, see bearerHeaderFrom().
     */
    protected function continueSessionFrom(TestResponse $response): void
    {
        $cookie = $response->getCookie(config('session.cookie'), false);
        $this->withUnencryptedCookie($cookie->getName(), $cookie->getValue());
    }

    /**
     * The `Authorization: Bearer <access_token>` header for a login/refresh
     * JSON response, ready to spread into withHeaders() on the next request.
     *
     * @return array<string, string>
     */
    protected function bearerHeaderFrom(TestResponse $response): array
    {
        return ['Authorization' => 'Bearer '.$response->json('access_token')];
    }
}
