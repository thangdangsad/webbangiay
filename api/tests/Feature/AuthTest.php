<?php

namespace Tests\Feature;

use App\Models\CustomerAccount;
use App\Models\Staff;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * These tests simulate real requests from the storefront/admin-panel SPAs:
 * a Referer header matching a configured Sanctum stateful domain is required
 * for EnsureFrontendRequestsAreStateful to turn on session/cookie auth at all
 * (see config/sanctum.php `stateful` + config/cors.php). CSRF is disabled here
 * because it's Sanctum/Laravel's own well-tested mechanism, not app logic —
 * what we're verifying is register/login/logout/me and guard isolation.
 */
class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withHeader('Referer', 'http://localhost:5180/');
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    /**
     * Carries the session cookie set by $response into the next request made
     * through $this, simulating the browser echoing the cookie back.
     */
    private function continueSessionFrom(TestResponse $response): void
    {
        $cookie = $response->getCookie(config('session.cookie'), false);
        $this->withUnencryptedCookie($cookie->getName(), $cookie->getValue());
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

    public function test_customer_can_login_then_access_me(): void
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
        $this->continueSessionFrom($login);

        $me = $this->getJson('/api/me');
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
        $this->continueSessionFrom($login);

        $me = $this->getJson('/api/admin/me');
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

    public function test_customer_session_cannot_access_admin_me(): void
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
        $this->continueSessionFrom($login);

        // authenticated as customer, but hitting the admin-guarded route
        $this->getJson('/api/admin/me')->assertStatus(401);
    }

    public function test_admin_session_cannot_access_customer_me(): void
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
        $this->continueSessionFrom($login);

        // authenticated as admin, but hitting the customer-guarded route
        $this->getJson('/api/me')->assertStatus(401);
    }
}
