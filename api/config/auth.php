<?php

// API stateless: không dùng session Laravel. Hai guard `customer` và `staff`
// được đăng ký ở App\Modules\Auth\AuthServiceProvider (JWT + phiên trong Redis).
return [
    'defaults' => ['guard' => 'customer', 'passwords' => 'users'],

    'guards' => [
        'customer' => ['driver' => 'jwt', 'provider' => 'users'],
        'staff' => ['driver' => 'jwt', 'provider' => 'staff_users'],
    ],

    'providers' => [
        'users' => ['driver' => 'eloquent', 'model' => App\Modules\User\Models\User::class],
        'staff_users' => ['driver' => 'eloquent', 'model' => App\Modules\User\Models\StaffUser::class],
    ],

    'passwords' => [],
    'password_timeout' => 10800,
];
