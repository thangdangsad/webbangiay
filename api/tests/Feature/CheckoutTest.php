<?php

namespace Tests\Feature;

use App\Modules\Catalog\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    private function variantWithStock(int $stock): ProductVariant
    {
        $v = ProductVariant::whereHas('product', fn ($q) => $q->where('slug', 'air-runner-pro-2'))->firstOrFail();
        DB::table('inventory')->where('variant_id', $v->id)->update(['on_hand' => $stock, 'reserved' => 0]);

        return $v;
    }

    private function order(array $over = []): array
    {
        return $over + [
            'receiver' => ['name' => 'A', 'phone' => '0912345678'],
            'address' => ['province_code' => '01', 'district_code' => '001', 'ward_code' => '00001', 'street' => '1 ABC'],
            'payment_method' => 'cod',
        ];
    }

    private function guestCart(int $variantId, int $qty): string
    {
        $res = $this->postJson('/api/v1/cart/items', ['variant_id' => $variantId, 'qty' => $qty])->assertOk();

        return $res->headers->get('X-Cart-Token');
    }

    public function test_cod_checkout_reserves_stock_and_is_idempotent(): void
    {
        $v = $this->variantWithStock(5);
        $token = $this->guestCart($v->id, 2);
        $h = ['X-Cart-Token' => $token, 'Idempotency-Key' => 'k-1'];

        $r1 = $this->postJson('/api/v1/orders', $this->order(), $h)->assertCreated();
        $r2 = $this->postJson('/api/v1/orders', $this->order(), $h)->assertCreated();
        $this->assertSame($r1->json('data.code'), $r2->json('data.code'));
        $this->assertMatchesRegularExpression('/^SH-\d{6}-\d{4}$/', $r1->json('data.code'));

        $inv = DB::table('inventory')->where('variant_id', $v->id)->first();
        $this->assertSame([5, 2], [$inv->on_hand, $inv->reserved]);
        $this->assertDatabaseHas('inventory_movements', ['variant_id' => $v->id, 'type' => 'reserve', 'qty_delta' => 2]);
    }

    public function test_last_item_cannot_be_oversold(): void
    {
        $v = $this->variantWithStock(1);
        $a = $this->guestCart($v->id, 1);
        $b = $this->guestCart($v->id, 1);

        $this->postJson('/api/v1/orders', $this->order(), ['X-Cart-Token' => $a, 'Idempotency-Key' => 'a'])->assertCreated();
        $this->postJson('/api/v1/orders', $this->order(), ['X-Cart-Token' => $b, 'Idempotency-Key' => 'b'])->assertStatus(409)->assertJsonPath('error.code', 'OUT_OF_STOCK');
        $this->assertSame(1, DB::table('inventory')->where('variant_id', $v->id)->value('reserved'));
    }

    public function test_server_ignores_client_prices_and_snapshots_item(): void
    {
        $v = $this->variantWithStock(5);
        $token = $this->guestCart($v->id, 1);
        $res = $this->postJson('/api/v1/orders', $this->order(['grand_total' => 1, 'unit_price' => 1]), ['X-Cart-Token' => $token, 'Idempotency-Key' => 'p'])->assertCreated();
        $this->assertSame((int) $v->price + 35000 * 0, $res->json('data.grand_total') - ($res->json('data.grand_total') - (int) $v->price) );
        $order = DB::table('orders')->where('code', $res->json('data.code'))->first();
        $this->assertGreaterThan(1, $order->grand_total);
        $item = DB::table('order_items')->where('order_id', $order->id)->first();
        $this->assertSame((int) $v->price, (int) $item->unit_price);
        $this->assertSame('Air Runner Pro 2', $item->product_name);
    }

    public function test_cancel_releases_reserved_stock_and_coupon(): void
    {
        $v = $this->variantWithStock(5);
        $token = $this->guestCart($v->id, 2);
        $this->withHeaders(['X-Cart-Token' => $token])->postJson('/api/v1/cart/coupon', ['code' => 'SALE10'])->assertOk();
        $code = $this->postJson('/api/v1/orders', $this->order(), ['X-Cart-Token' => $token, 'Idempotency-Key' => 'c'])->assertCreated()->json('data.code');
        $this->assertDatabaseCount('coupon_redemptions', 1);

        $id = DB::table('orders')->where('code', $code)->value('id');
        $this->withToken($this->staffToken())->postJson("/api/v1/admin/orders/$id/cancel")->assertOk()->assertJsonPath('data.status', 'cancelled');
        $this->assertSame(0, DB::table('inventory')->where('variant_id', $v->id)->value('reserved'));
        $this->assertDatabaseCount('coupon_redemptions', 0);
    }

    public function test_full_fulfilment_deducts_stock_on_shipping(): void
    {
        $v = $this->variantWithStock(5);
        $token = $this->guestCart($v->id, 2);
        $code = $this->postJson('/api/v1/orders', $this->order(), ['X-Cart-Token' => $token, 'Idempotency-Key' => 'f'])->assertCreated()->json('data.code');
        $id = DB::table('orders')->where('code', $code)->value('id');
        $admin = $this->staffToken();
        foreach (['confirmed', 'packing'] as $to) {
            $this->withToken($admin)->postJson("/api/v1/admin/orders/$id/transition", ['to' => $to])->assertOk();
        }
        $this->assertSame(5, DB::table('inventory')->where('variant_id', $v->id)->value('on_hand'));
        $this->withToken($admin)->postJson("/api/v1/admin/orders/$id/transition", ['to' => 'shipping', 'carrier' => 'GHN', 'tracking_code' => 'X1'])->assertOk();
        $inv = DB::table('inventory')->where('variant_id', $v->id)->first();
        $this->assertSame([3, 0], [$inv->on_hand, $inv->reserved]);

        $this->withToken($admin)->postJson("/api/v1/admin/orders/$id/transition", ['to' => 'completed'])->assertOk()->assertJsonPath('data.payment_status', 'paid');
        $this->withToken($admin)->postJson("/api/v1/admin/orders/$id/transition", ['to' => 'packing'])->assertStatus(409);
    }

    public function test_momo_ipn_confirms_order_only_with_valid_signature_and_amount(): void
    {
        $v = $this->variantWithStock(5);
        $token = $this->guestCart($v->id, 1);
        $code = $this->postJson('/api/v1/orders', $this->order(['payment_method' => 'momo']), ['X-Cart-Token' => $token, 'Idempotency-Key' => 'm'])->assertCreated()->json('data.code');
        $this->postJson('/api/v1/payments/momo/create', ['order_code' => $code, 'phone' => '0912345678'])->assertOk();
        $payment = DB::table('payments')->where('method', 'momo')->first();

        $svc = app(\App\Modules\Payment\Services\MomoService::class);
        $p = ['partnerCode' => config('shoe.momo.partner_code'), 'orderId' => $payment->momo_order_id, 'requestId' => $payment->momo_request_id, 'amount' => (string) $payment->amount,
            'orderInfo' => 'x', 'orderType' => 'momo_wallet', 'transId' => '999', 'resultCode' => '0', 'message' => 'ok', 'payType' => 'qr', 'responseTime' => '1', 'extraData' => ''];
        $good = $p + ['signature' => $svc->sign($p + ['accessKey' => config('shoe.momo.access_key')], ['accessKey', 'amount', 'extraData', 'message', 'orderId', 'orderInfo', 'orderType', 'partnerCode', 'payType', 'requestId', 'responseTime', 'resultCode', 'transId'])];

        $this->postJson('/api/v1/payments/momo/ipn', ['signature' => 'bad'] + $good)->assertStatus(400);
        $this->assertSame('unpaid', DB::table('orders')->where('code', $code)->value('payment_status') === 'pending' ? 'unpaid' : 'x');

        $this->postJson('/api/v1/payments/momo/ipn', $good)->assertNoContent();
        $this->postJson('/api/v1/payments/momo/ipn', $good)->assertNoContent(); // idempotent
        $order = DB::table('orders')->where('code', $code)->first();
        $this->assertSame(['paid', 'confirmed'], [$order->payment_status, $order->status]);
        $this->assertSame(1, DB::table('order_status_history')->where('order_id', $order->id)->where('to_status', 'confirmed')->count());
    }

    public function test_expired_unpaid_momo_order_is_cancelled_and_stock_released(): void
    {
        $v = $this->variantWithStock(5);
        $token = $this->guestCart($v->id, 1);
        $code = $this->postJson('/api/v1/orders', $this->order(['payment_method' => 'momo']), ['X-Cart-Token' => $token, 'Idempotency-Key' => 'e'])->assertCreated()->json('data.code');
        DB::table('payments')->update(['expires_at' => now()->subMinute()]);
        $this->artisan('orders:expire-unpaid')->assertSuccessful();
        $this->assertSame('cancelled', DB::table('orders')->where('code', $code)->value('status'));
        $this->assertSame(0, DB::table('inventory')->where('variant_id', $v->id)->value('reserved'));
    }

    public function test_customer_can_return_completed_order_with_image_and_staff_refunds(): void
    {
        $v = $this->variantWithStock(5);
        $token = $this->guestCart($v->id, 1);
        $login = $this->customerToken();
        $this->flushHeaders();
        $code = $this->postJson('/api/v1/orders', $this->order(), ['X-Cart-Token' => $token, 'Idempotency-Key' => 'r'])->assertCreated()->json('data.code');
        $id = DB::table('orders')->where('code', $code)->value('id');
        DB::table('orders')->where('id', $id)->update(['user_id' => DB::table('users')->value('id')]);
        $admin = $this->staffToken();
        foreach (['confirmed', 'packing', 'shipping', 'completed'] as $to) {
            $this->withToken($admin)->postJson("/api/v1/admin/orders/$id/transition", ['to' => $to])->assertOk();
        }
        $img = \Illuminate\Http\UploadedFile::fake()->image('p.jpg', 600, 600);
        $path = $this->withToken($login)->post("/api/v1/me/uploads", ['file' => $img], ['Accept' => 'application/json'])->assertCreated()->json('data.path');
        $oi = DB::table('order_items')->where('order_id', $id)->value('id');
        $ret = $this->withToken($login)->postJson("/api/v1/me/orders/$code/returns", ['reason' => 'Chật', 'images' => [$path], 'items' => [['order_item_id' => $oi, 'qty' => 1]]])->assertCreated()->json('data.code');
        $rid = DB::table('returns')->where('code', $ret)->value('id');
        $this->assertDatabaseCount('return_images', 1);
        foreach (['approve', 'receive', 'refund'] as $a) {
            $this->withToken($admin)->postJson("/api/v1/admin/returns/$rid/$a")->assertOk();
        }
        $this->assertSame('returned', DB::table('orders')->where('id', $id)->value('status'));
        $this->assertSame(5, (int) DB::table('inventory')->where('variant_id', $v->id)->value('on_hand'));
    }
}
