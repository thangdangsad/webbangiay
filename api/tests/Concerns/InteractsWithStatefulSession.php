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
     */
    protected function continueSessionFrom(TestResponse $response): void
    {
        $cookie = $response->getCookie(config('session.cookie'), false);
        $this->withUnencryptedCookie($cookie->getName(), $cookie->getValue());
    }
}
