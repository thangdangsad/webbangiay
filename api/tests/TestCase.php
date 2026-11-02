<?php

namespace Tests;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Redis;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = DatabaseSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['auth', 'cache'] as $c) {
            Redis::connection($c)->flushdb();
        }
    }

    protected function customerToken(string $email = 'khach@solehouse.vn', string $password = 'Khach@123'): string
    {
        return $this->flushHeaders()->postJson('/api/v1/auth/login', ['identifier' => $email, 'password' => $password])->assertOk()->json('access_token');
    }

    protected function staffToken(string $email = 'admin@solehouse.vn', string $password = 'Admin@123'): string
    {
        return $this->flushHeaders()->postJson('/api/v1/admin/auth/login', ['email' => $email, 'password' => $password])->assertOk()->json('access_token');
    }
}
