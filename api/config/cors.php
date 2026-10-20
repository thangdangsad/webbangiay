<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // storefront (Vite dev server), first-party SPA. 5173 is reserved by
    // Sail/compose.yaml for Laravel's own unused built-in Vite assets, and
    // 5174 is already bound on the dev host by an unrelated project, so
    // storefront uses 5180 instead. A future admin-panel app gets its own
    // origin added here (e.g. http://localhost:5175) — keep this a list so
    // adding that origin later doesn't require restructuring it.
    'allowed_origins' => [
        'http://localhost:5180',
        'http://127.0.0.1:5180',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
