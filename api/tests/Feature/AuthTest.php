<?php

namespace Tests\Feature;

use Tests\TestCase;

class AuthTest extends TestCase
{
    private array $h = ['Origin' => 'http://localhost:3000', 'X-Requested-With' => 'XMLHttpRequest'];

    private function refreshWith(string $token, ?array $headers = null)
    {
        return $this->call('POST', '/api/v1/auth/refresh', [], ['rt_customer' => $token], [], $this->transformHeadersToServerVars($headers ?? $this->h) + ['HTTP_ACCEPT' => 'application/json']);
    }

    private function cookie($res, string $name = 'rt_customer'): string
    {
        return collect($res->headers->getCookies())->first(fn ($c) => $c->getName() === $name)->getValue();
    }

    public function test_login_returns_access_token_and_httponly_refresh_cookie(): void
    {
        $res = $this->postJson('/api/v1/auth/login', ['identifier' => 'khach@solehouse.vn', 'password' => 'Khach@123'])->assertOk();
        $c = collect($res->headers->getCookies())->first(fn ($c) => $c->getName() === 'rt_customer');
        $this->assertTrue($c->isHttpOnly());
        $this->assertSame('strict', $c->getSameSite());
        $this->assertSame('/api/v1/auth', $c->getPath());
        $this->withToken($res->json('access_token'))->getJson('/api/v1/me')->assertOk()->assertJsonPath('data.email', 'khach@solehouse.vn');
    }

    public function test_wrong_password_is_rejected_and_locks_after_five_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', ['identifier' => 'khach@solehouse.vn', 'password' => 'nope'])->assertStatus(401)->assertJsonPath('error.code', 'INVALID_CREDENTIALS');
        }
        $this->postJson('/api/v1/auth/login', ['identifier' => 'khach@solehouse.vn', 'password' => 'Khach@123'])->assertStatus(429)->assertJsonPath('error.code', 'LOGIN_LOCKED');
    }

    public function test_refresh_rotates_token_and_reuse_after_grace_kills_session(): void
    {
        $login = $this->postJson('/api/v1/auth/login', ['identifier' => 'khach@solehouse.vn', 'password' => 'Khach@123']);
        $old = $this->cookie($login);

        $r1 = $this->refreshWith($old)->assertOk();
        $new = $this->cookie($r1);
        $this->assertNotSame($old, $new);

        // trong cửa sổ ân hạn: cấp access token nhưng không xoay refresh
        $this->refreshWith($old)->assertOk();

        config(['shoe.auth.refresh_grace' => -1]); // hết cửa sổ ân hạn
        $this->refreshWith($old)
            ->assertStatus(401)->assertJsonPath('error.code', 'REFRESH_REUSED');
        // phiên bị hủy: refresh token hiện hành cũng không dùng được
        $this->refreshWith($new)->assertStatus(401);
    }

    public function test_refresh_requires_origin_and_xhr_header(): void
    {
        $login = $this->postJson('/api/v1/auth/login', ['identifier' => 'khach@solehouse.vn', 'password' => 'Khach@123']);
        $this->refreshWith($this->cookie($login), [])->assertForbidden();
        $this->refreshWith($this->cookie($login), ['Origin' => 'http://evil.test', 'X-Requested-With' => 'XMLHttpRequest'])->assertForbidden();
    }

    public function test_logout_revokes_access_token_immediately(): void
    {
        $t = $this->customerToken();
        $this->withToken($t)->postJson('/api/v1/auth/logout', [], $this->h)->assertOk();
        $this->withToken($t)->getJson('/api/v1/me')->assertStatus(401)->assertJsonPath('error.code', 'SESSION_REVOKED');
    }

    public function test_change_password_revokes_all_sessions(): void
    {
        $a = $this->customerToken();
        $b = $this->customerToken();
        $this->withToken($a)->putJson('/api/v1/me/password', ['current_password' => 'Khach@123', 'password' => 'NewPass@123'])->assertOk();
        $this->withToken($b)->getJson('/api/v1/me')->assertStatus(401);
        $this->customerToken('khach@solehouse.vn', 'NewPass@123');
    }

    public function test_customer_token_cannot_access_admin_and_vice_versa(): void
    {
        $this->withToken($this->customerToken())->getJson('/api/v1/admin/me')->assertStatus(401);
        $this->withToken($this->staffToken())->getJson('/api/v1/me')->assertStatus(401);
    }

    public function test_staff_rbac_is_enforced(): void
    {
        $kho = $this->staffToken('kho@solehouse.vn', 'Kho@12345');
        $this->withToken($kho)->getJson('/api/v1/admin/staff')->assertForbidden();
        $this->withToken($kho)->getJson('/api/v1/admin/dashboard/summary')->assertForbidden();
        $this->withToken($kho)->getJson('/api/v1/admin/inventory')->assertOk();
        $this->withToken($this->staffToken())->getJson('/api/v1/admin/staff')->assertOk();
    }

    public function test_max_sessions_evicts_oldest(): void
    {
        $tokens = [];
        for ($i = 0; $i < 6; $i++) {
            $tokens[] = $this->customerToken();
            $this->travel(1)->seconds();
        }
        $this->withToken($tokens[0])->getJson('/api/v1/me')->assertStatus(401);
        $this->withToken($tokens[5])->getJson('/api/v1/me')->assertOk();
    }
}
