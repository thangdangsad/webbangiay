<?php

namespace Tests\Feature;

use App\Models\CustomerAccount;
use App\Models\Staff;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * These tests simulate real requests from the storefront/admin-panel SPAs.
 * A Referer header matching a configured Sanctum stateful domain is
 * required for EnsureFrontendRequestsAreStateful to start a session at all
 * (needed for CartResolver's guest-cart merge on login, see
 * config/sanctum.php `stateful` + config/cors.php). CSRF is disabled here
 * because it's Sanctum/Laravel's own well-tested mechanism, not app logic.
 *
 * Login/logout/me/refresh themselves are NOT session-based: login returns
 * an access token (Bearer, short-lived) and sets a refresh token as an
 * HttpOnly cookie (long-lived) — both opaque strings stored in Redis via
 * App\Services\Auth\TokenBroker, never in the database. See
 * App\Http\Controllers\Auth\Concerns\IssuesAuthTokens.
 */
class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withHeader('Referer', 'http://localhost:5180/');
        $this->withoutMiddleware(ValidateCsrfToken::class);

        // postJson()/getJson() only send cookies when this is set (mirrors
        // axios' `withCredentials: true` — see storefront/admin-panel's
        // api/client.js) — needed so the refresh-token cookie round-trips.
        $this->withCredentials();
    }

    /**
     * Carries the refresh-token cookie set by $response (login/refresh)
     * into the next request made through $this.
     */
    private function continueRefreshCookieFrom(TestResponse $response, string $cookieName): void
    {
        $cookie = $response->getCookie($cookieName, false);
        $this->withUnencryptedCookie($cookie->getName(), $cookie->getValue());
    }

    /**
     * @return array<string, string>
     */
    private function bearerHeader(TestResponse $response): array
    {
        return ['Authorization' => 'Bearer '.$response->json('access_token')];
    }

    public function test_customer_can_register(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Nguyen Van A',
            'email' => 'customer-a@example.com',
            'phone' => '0900000001',
            'address' => '123 Le Loi, HCMC',
            'password' => 'secret123',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.user_email', 'customer-a@example.com');

        $this->assertDatabaseHas('quanli_user', [
            'user_email' => 'customer-a@example.com',
        ]);

        // password must be stored hashed, never plaintext
        $customer = CustomerAccount::where('user_email', 'customer-a@example.com')->first();
        $this->assertTrue(Hash::check('secret123', $customer->user_password));
    }

    public function test_customer_can_login_then_access_me_with_access_token(): void
    {
        CustomerAccount::create([
            'user_name' => 'Nguyen Van B',
            'user_email' => 'customer-b@example.com',
            'user_phone' => '0900000002',
            'user_address' => '456 Le Loi, HCMC',
            'user_password' => Hash::make('secret123'),
            'user_code' => 0,
        ]);

        $login = $this->postJson('/api/login', [
            'email' => 'customer-b@example.com',
            'password' => 'secret123',
        ]);
        $login->assertOk();
        $this->assertIsString($login->json('access_token'));
        $this->assertIsInt($login->json('expires_in'));
        $login->assertCookie('customer_refresh_token');

        $me = $this->withHeaders($this->bearerHeader($login))->getJson('/api/me');
        $me->assertOk();
        $me->assertJsonPath('data.user_email', 'customer-b@example.com');
    }

    public function test_customer_login_lazily_migrates_legacy_plaintext_password(): void
    {
        // Simulate a legacy row inserted with a raw plaintext password,
        // bypassing Eloquent's "hashed" cast, just like the old PHP app did.
        $id = DB::table('quanli_user')->insertGetId([
            'user_name' => 'Legacy Customer',
            'user_email' => 'legacy-customer@example.com',
            'user_phone' => '0900000003',
            'user_address' => '789 Le Loi, HCMC',
            'user_password' => 'plaintext-pass',
            'user_code' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $login = $this->postJson('/api/login', [
            'email' => 'legacy-customer@example.com',
            'password' => 'plaintext-pass',
        ]);
        $login->assertOk();

        // password must now be rehashed to bcrypt in the database
        $stored = DB::table('quanli_user')->where('user_id', $id)->value('user_password');
        $this->assertNotSame('plaintext-pass', $stored);
        $this->assertTrue(Hash::check('plaintext-pass', $stored));
    }

    public function test_customer_login_with_wrong_password_is_rejected(): void
    {
        CustomerAccount::create([
            'user_name' => 'Nguyen Van C',
            'user_email' => 'customer-c@example.com',
            'user_phone' => '0900000004',
            'user_address' => '1 Le Loi, HCMC',
            'user_password' => Hash::make('correct-password'),
            'user_code' => 0,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'customer-c@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401);
    }

    public function test_guest_cannot_access_me(): void
    {
        $this->getJson('/api/me')->assertStatus(401);
    }

    public function test_customer_can_refresh_access_token_using_cookie(): void
    {
        $customer = CustomerAccount::create([
            'user_name' => 'Nguyen Van E',
            'user_email' => 'customer-e@example.com',
            'user_phone' => '0900000006',
            'user_address' => '3 Le Loi, HCMC',
            'user_password' => Hash::make('secret123'),
            'user_code' => 0,
        ]);

        $login = $this->postJson('/api/login', [
            'email' => 'customer-e@example.com',
            'password' => 'secret123',
        ]);
        $login->assertOk();
        $this->continueRefreshCookieFrom($login, 'customer_refresh_token');

        $refresh = $this->postJson('/api/refresh');
        $refresh->assertOk();
        $refresh->assertJsonPath('data.user_id', $customer->user_id);
        $this->assertNotSame($login->json('access_token'), $refresh->json('access_token'));

        $me = $this->withHeaders($this->bearerHeader($refresh))->getJson('/api/me');
        $me->assertOk();
        $me->assertJsonPath('data.user_email', 'customer-e@example.com');
    }

    public function test_refresh_token_is_single_use(): void
    {
        CustomerAccount::create([
            'user_name' => 'Nguyen Van F',
            'user_email' => 'customer-f@example.com',
            'user_phone' => '0900000007',
            'user_address' => '4 Le Loi, HCMC',
            'user_password' => Hash::make('secret123'),
            'user_code' => 0,
        ]);

        $login = $this->postJson('/api/login', [
            'email' => 'customer-f@example.com',
            'password' => 'secret123',
        ]);
        $this->continueRefreshCookieFrom($login, 'customer_refresh_token');

        $this->postJson('/api/refresh')->assertOk();

        // the cookie in $this is still the OLD (already-rotated) refresh
        // token — replaying it must fail.
        $this->postJson('/api/refresh')->assertStatus(401);
    }

    public function test_customer_logout_revokes_access_and_refresh_tokens(): void
    {
        CustomerAccount::create([
            'user_name' => 'Nguyen Van G',
            'user_email' => 'customer-g@example.com',
            'user_phone' => '0900000008',
            'user_address' => '5 Le Loi, HCMC',
            'user_password' => Hash::make('secret123'),
            'user_code' => 0,
        ]);

        $login = $this->postJson('/api/login', [
            'email' => 'customer-g@example.com',
            'password' => 'secret123',
        ]);
        $this->continueRefreshCookieFrom($login, 'customer_refresh_token');

        $this->withHeaders($this->bearerHeader($login))->postJson('/api/logout')->assertNoContent();

        // RequestGuard caches the resolved user on itself for the lifetime
        // of the request it was built with, and that guard instance is
        // reused across simulated requests within a single test — forget
        // it so the next request re-resolves from the (now revoked) token
        // instead of returning the cached pre-logout user.
        Auth::forgetGuards();

        $this->withHeaders($this->bearerHeader($login))->getJson('/api/me')->assertStatus(401);
        $this->postJson('/api/refresh')->assertStatus(401);
    }

    public function test_admin_can_login_independently_via_admin_guard(): void
    {
        Staff::create([
            'user_name' => 'Admin One',
            'user_email' => 'admin-a@example.com',
            'user_phone' => '0911111111',
            'user_address' => 'HQ',
            'user_password' => Hash::make('adminpass123'),
            'role' => Staff::ROLE_ADMIN,
        ]);

        $login = $this->postJson('/api/admin/login', [
            'email' => 'admin-a@example.com',
            'password' => 'adminpass123',
        ]);
        $login->assertOk();
        $login->assertCookie('admin_refresh_token');

        $me = $this->withHeaders($this->bearerHeader($login))->getJson('/api/admin/me');
        $me->assertOk();
        $me->assertJsonPath('data.user_email', 'admin-a@example.com');
    }

    public function test_admin_login_lazily_migrates_legacy_md5_password(): void
    {
        $id = DB::table('quanly_taikhoan')->insertGetId([
            'user_name' => 'Legacy Admin',
            'user_email' => 'legacy-admin@example.com',
            'user_phone' => '0922222222',
            'user_address' => 'HQ',
            'user_password' => md5('legacy-md5-pass'),
            'role' => Staff::ROLE_ADMIN,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $login = $this->postJson('/api/admin/login', [
            'email' => 'legacy-admin@example.com',
            'password' => 'legacy-md5-pass',
        ]);
        $login->assertOk();

        $stored = DB::table('quanly_taikhoan')->where('user_id', $id)->value('user_password');
        $this->assertNotSame(md5('legacy-md5-pass'), $stored);
        $this->assertTrue(Hash::check('legacy-md5-pass', $stored));
    }

    public function test_customer_access_token_cannot_access_admin_me(): void
    {
        CustomerAccount::create([
            'user_name' => 'Nguyen Van D',
            'user_email' => 'customer-d@example.com',
            'user_phone' => '0900000005',
            'user_address' => '2 Le Loi, HCMC',
            'user_password' => Hash::make('secret123'),
            'user_code' => 0,
        ]);

        $login = $this->postJson('/api/login', [
            'email' => 'customer-d@example.com',
            'password' => 'secret123',
        ]);
        $login->assertOk();

        // authenticated as customer, but hitting the admin-guarded route
        $this->withHeaders($this->bearerHeader($login))->getJson('/api/admin/me')->assertStatus(401);
    }

    public function test_admin_access_token_cannot_access_customer_me(): void
    {
        Staff::create([
            'user_name' => 'Admin Two',
            'user_email' => 'admin-b@example.com',
            'user_phone' => '0933333333',
            'user_address' => 'HQ',
            'user_password' => Hash::make('adminpass123'),
            'role' => Staff::ROLE_STAFF,
        ]);

        $login = $this->postJson('/api/admin/login', [
            'email' => 'admin-b@example.com',
            'password' => 'adminpass123',
        ]);
        $login->assertOk();

        // authenticated as admin, but hitting the customer-guarded route
        $this->withHeaders($this->bearerHeader($login))->getJson('/api/me')->assertStatus(401);
    }
}
