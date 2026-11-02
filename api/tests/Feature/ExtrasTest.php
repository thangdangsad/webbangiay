<?php

namespace Tests\Feature;

use App\Modules\Auth\Totp;
use App\Modules\Catalog\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExtrasTest extends TestCase
{
    public function test_staff_two_factor_setup_login_and_verify(): void
    {
        $t = $this->staffToken('manager@solehouse.vn', 'Manager@123');
        $secret = $this->withToken($t)->postJson('/api/v1/admin/auth/2fa/setup')->assertOk()->json('data.secret');
        $this->withToken($t)->postJson('/api/v1/admin/auth/2fa/enable', ['code' => '000000'])->assertStatus(422);
        $this->withToken($t)->postJson('/api/v1/admin/auth/2fa/enable', ['code' => Totp::code($secret)])->assertOk();

        $this->flushHeaders();
        $res = $this->postJson('/api/v1/admin/auth/login', ['email' => 'manager@solehouse.vn', 'password' => 'Manager@123'])->assertOk()->assertJsonPath('data.requires_2fa', true);
        $this->assertNull($res->json('access_token'));
        $challenge = $res->json('data.challenge_token');
        $this->postJson('/api/v1/admin/auth/2fa/verify', ['challenge_token' => $challenge, 'code' => '123456'])->assertStatus(401);
        $this->postJson('/api/v1/admin/auth/2fa/verify', ['challenge_token' => $challenge, 'code' => Totp::code($secret)])->assertStatus(401); // challenge đã dùng một lần
    }

    public function test_two_factor_verify_issues_tokens(): void
    {
        $t = $this->staffToken('manager@solehouse.vn', 'Manager@123');
        $secret = $this->withToken($t)->postJson('/api/v1/admin/auth/2fa/setup')->json('data.secret');
        $this->withToken($t)->postJson('/api/v1/admin/auth/2fa/enable', ['code' => Totp::code($secret)])->assertOk();
        $this->flushHeaders();
        $challenge = $this->postJson('/api/v1/admin/auth/login', ['email' => 'manager@solehouse.vn', 'password' => 'Manager@123'])->json('data.challenge_token');
        $this->postJson('/api/v1/admin/auth/2fa/verify', ['challenge_token' => $challenge, 'code' => Totp::code($secret)])->assertOk()->assertJsonStructure(['access_token']);
    }

    public function test_product_image_upload_is_converted_to_webp_and_moved_on_save(): void
    {
        Storage::fake('public');
        $admin = $this->staffToken();
        $up = $this->withToken($admin)->post('/api/v1/admin/uploads', ['file' => UploadedFile::fake()->image('a.jpg', 900, 900), 'type' => 'product'], ['Accept' => 'application/json'])->assertCreated();
        $path = $up->json('data.path');
        $this->assertStringStartsWith('tmp/', $path);
        $this->assertStringEndsWith('.webp', $path);
        Storage::disk('public')->assertExists([$path, str_replace('.webp', '_md.webp', $path), str_replace('.webp', '_sm.webp', $path)]);

        $this->withToken($admin)->post('/api/v1/admin/uploads', ['file' => UploadedFile::fake()->image('s.jpg', 100, 100), 'type' => 'product'], ['Accept' => 'application/json'])->assertStatus(422);
        $this->withToken($admin)->post('/api/v1/admin/uploads', ['file' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'), 'type' => 'product'], ['Accept' => 'application/json'])->assertStatus(422);

        $p = Product::first();
        $this->withToken($admin)->putJson("/api/v1/admin/products/{$p->id}", ['name' => $p->name, 'status' => 'active', 'images' => [['path' => $path, 'is_primary' => true]]])->assertOk();
        $final = DB::table('product_images')->where('product_id', $p->id)->value('path');
        $this->assertSame("products/{$p->id}/".basename($path), $final);
        Storage::disk('public')->assertExists($final);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_google_login_creates_user_and_rejects_wrong_audience(): void
    {
        config(['shoe.google.client_id' => 'cid.apps.googleusercontent.com']);
        $good = ['aud' => 'cid.apps.googleusercontent.com', 'iss' => 'https://accounts.google.com', 'email' => 'new@gmail.com', 'email_verified' => 'true', 'name' => 'New User', 'exp' => (string) (time() + 600)];
        Http::fake(['oauth2.googleapis.com/*' => Http::sequence()->push($good)->push($good)->push(array_merge($good, ['aud' => 'other']))]);

        $this->postJson('/api/v1/auth/google', ['credential' => 'x'])->assertCreated()->assertJsonPath('user.email', 'new@gmail.com')->assertJsonPath('user.has_password', false);
        $this->postJson('/api/v1/auth/google', ['credential' => 'x'])->assertOk(); // lần 2: đăng nhập tài khoản đã có
        $this->assertSame(1, DB::table('users')->where('email', 'new@gmail.com')->count());
        $this->postJson('/api/v1/auth/google', ['credential' => 'x'])->assertStatus(401)->assertJsonPath('error.code', 'GOOGLE_INVALID');
    }

    public function test_google_login_disabled_without_client_id(): void
    {
        config(['shoe.google.client_id' => null]);
        $this->postJson('/api/v1/auth/google', ['credential' => 'x'])->assertStatus(503);
    }

    public function test_new_order_emails_staff_and_customer(): void
    {
        $v = \App\Modules\Catalog\Models\ProductVariant::first();
        $res = $this->postJson('/api/v1/cart/items', ['variant_id' => $v->id, 'qty' => 1])->assertOk();
        $this->postJson('/api/v1/orders', [
            'receiver' => ['name' => 'A', 'phone' => '0912345678', 'email' => 'c@x.vn'],
            'address' => ['province_code' => '01', 'district_code' => '001', 'ward_code' => '00001', 'street' => '1 ABC'], 'payment_method' => 'cod',
        ], ['X-Cart-Token' => $res->headers->get('X-Cart-Token'), 'Idempotency-Key' => 'mail1'])->assertCreated();
        $to = collect(Mail::mailer()->getSymfonyTransport()->messages())->flatMap(fn ($m) => collect($m->getOriginalMessage()->getTo())->map(fn ($a) => $a->getAddress()))->all();
        $this->assertContains('c@x.vn', $to);
        $this->assertContains('admin@solehouse.vn', $to);
        $this->assertContains('cskh@solehouse.vn', $to);
    }

    public function test_review_requires_purchase_and_moderation(): void
    {
        $customer = $this->customerToken();
        $product = Product::first();
        $this->withToken($customer)->postJson("/api/v1/products/{$product->slug}/reviews", ['rating' => 5])->assertForbidden();

        $uid = DB::table('users')->value('id');
        $oid = DB::table('orders')->insertGetId(['code' => 'SH-000000-0001', 'user_id' => $uid, 'customer_name' => 'A', 'phone' => '+84901234567', 'ship_address' => '{}', 'status' => 'completed', 'payment_status' => 'paid', 'payment_method' => 'cod', 'subtotal' => 1, 'grand_total' => 1, 'placed_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        DB::table('order_items')->insert(['order_id' => $oid, 'product_id' => $product->id, 'product_name' => 'x', 'sku' => 's', 'color' => 'c', 'size' => '40', 'unit_price' => 1, 'qty' => 1, 'line_total' => 1]);
        $this->withToken($customer)->postJson("/api/v1/products/{$product->slug}/reviews", ['rating' => 5, 'content' => 'Tốt'])->assertCreated();
        $this->getJson("/api/v1/products/{$product->slug}/reviews")->assertJsonCount(0, 'data');

        $admin = $this->staffToken();
        $rid = $this->withToken($admin)->getJson('/api/v1/admin/reviews?status=pending')->assertOk()->json('data.0.id');
        $this->withToken($admin)->patchJson("/api/v1/admin/reviews/$rid", ['status' => 'approved'])->assertOk();
        $this->flushHeaders()->getJson("/api/v1/products/{$product->slug}/reviews")->assertJsonCount(1, 'data');
    }

    public function test_sales_summary_and_reconcile_commands_run(): void
    {
        $this->artisan('reports:summarize', ['--all' => true])->assertSuccessful();
        $this->artisan('payments:reconcile')->assertSuccessful();
        $this->artisan('docs:openapi', ['--out' => sys_get_temp_dir().'/openapi-test.json'])->assertSuccessful();
        $this->assertArrayHasKey('/orders', json_decode(file_get_contents(sys_get_temp_dir().'/openapi-test.json'), true)['paths']);
    }
}
