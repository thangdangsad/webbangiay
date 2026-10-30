<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cache Store
    |--------------------------------------------------------------------------
    |
    | Which cache store (config/cache.php) App\Services\Auth\TokenBroker
    | reads/writes to — "redis" in dev/production. Tests override this to
    | "array" (see phpunit.xml) so they never touch real Redis.
    |
    */

    'store' => env('TOKEN_AUTH_CACHE_STORE', 'redis'),

    /*
    |--------------------------------------------------------------------------
    | Access / Refresh Token Lifetimes
    |--------------------------------------------------------------------------
    |
    | Both tokens are opaque random strings stored via the store above (not
    | the database). The access token is short-lived and sent as a Bearer
    | header; the refresh token is long-lived and sent as an HttpOnly
    | cookie. Seconds.
    |
    */

    'access_ttl' => (int) env('ACCESS_TOKEN_TTL', 3600),

    'refresh_ttl' => (int) env('REFRESH_TOKEN_TTL', 1209600),

    /*
    |--------------------------------------------------------------------------
    | Refresh Token Cookie
    |--------------------------------------------------------------------------
    */

    'cookie_secure' => (bool) env('REFRESH_TOKEN_COOKIE_SECURE', false),

    'cookie_same_site' => env('REFRESH_TOKEN_COOKIE_SAME_SITE', 'lax'),

];
