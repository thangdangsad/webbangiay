<?php

return [
    'frontends' => [
        'storefront' => env('FRONTEND_STOREFRONT_URL', 'http://localhost:3000'),
        'admin' => env('FRONTEND_ADMIN_URL', 'http://localhost:5173'),
    ],

    // Thông số xác thực — xem docs/docs/02-BE-design.md §6.2
    'auth' => [
        'customer' => [
            'secret' => env('JWT_CUSTOMER_SECRET', env('APP_KEY')),
            'kid' => env('JWT_CUSTOMER_KID', 'c1'),
            'access_ttl' => 15 * 60,
            'refresh_ttl' => 30 * 86400,          // trượt
            'refresh_absolute' => 90 * 86400,
            'idle_ttl' => null,
            'max_sessions' => 5,
            'cookie' => 'rt_customer',
            'cookie_path' => '/api/v1/auth',
        ],
        'staff' => [
            'secret' => env('JWT_STAFF_SECRET', env('APP_KEY').'-staff'),
            'kid' => env('JWT_STAFF_KID', 's1'),
            'access_ttl' => 10 * 60,
            'refresh_ttl' => 12 * 3600,           // tuyệt đối
            'refresh_absolute' => 12 * 3600,
            'idle_ttl' => 2 * 3600,
            'max_sessions' => 2,
            'cookie' => 'rt_staff',
            'cookie_path' => '/api/v1/admin/auth',
        ],
        'refresh_grace' => 10,
        'cookie_secure' => (bool) env('AUTH_COOKIE_SECURE', false),
        'login_max_attempts' => 5,
        'login_lock_seconds' => 15 * 60,
        'staff_2fa_roles' => ['admin', 'manager'],
        'staff_2fa_enforced' => (bool) env('STAFF_2FA_ENFORCED', false),
    ],

    'order' => [
        'momo_expire_minutes' => 15,
        'free_ship_threshold' => 1000000,
        'return_days' => 7,
    ],

    // Seeder: tạo 16 sản phẩm DEMO (ảnh vẽ). Mặc định tắt — dùng dữ liệu thật qua data-migration/.
    'seed_demo_products' => (bool) env('SEED_DEMO_PRODUCTS', false),

    'google' => ['client_id' => env('GOOGLE_CLIENT_ID')],

    'search' => [
        'driver' => env('SEARCH_DRIVER', 'mysql'),
        'host' => env('MEILISEARCH_HOST', 'http://localhost:7700'),
        'key' => env('MEILISEARCH_KEY'),
        'index' => 'products',
    ],

    'momo' => [
        'partner_code' => env('MOMO_PARTNER_CODE', 'MOMOBKUN20180529'),
        'access_key' => env('MOMO_ACCESS_KEY', 'klm05TvNBzhg7h7j'),
        'secret_key' => env('MOMO_SECRET_KEY', 'at67qH6mk8w5Y1nAyMoYKMWACiEi2bsa'),
        'endpoint' => env('MOMO_ENDPOINT', 'https://test-payment.momo.vn'),
        'fake' => (bool) env('MOMO_FAKE', true),   // dev: giả lập cổng MoMo khi không có kết nối
    ],
];
