<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithStatefulSession;
use Tests\TestCase;

/**
 * COD-only checkout. Equivalent of frontend/thanhtoan.php's `payments=="0"`
 * branch (Momo/VNPay/PayPal branches are a later phase, see OrderService).
 */
class CheckoutTest extends TestCase
{
    use InteractsWithStatefulSession, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsStatefulSpa();
    }

    public function test_cod_checkout_creates_order_rows_and_empties_the_cart(): void
    {
        $productA = Product::factory()->create(['product_price' => 100000, 'product_discount' => 90000]);
        $productB = Product::factory()->create(['product_price' => 200000, 'product_discount' => 150000]);

        $addA = $this->postJson('/api/cart/items', ['product_id' => $productA->product_id, 'quantity' => 2]);
        $this->continueSessionFrom($addA);
        $this->postJson('/api/cart/items', ['product_id' => $productB->product_id, 'quantity' => 1])->assertCreated();

        $response = $this->postJson('/api/checkout', [
            'name' => 'Nguyen Van Khach',
            'phone' => '0900000099',
            'email' => 'khach@example.com',
            'address' => '1 Le Loi, HCMC',
            'note' => 'Giao giờ hành chính',
            'payments' => '0',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('customer', [
            'customer_name' => 'Nguyen Van Khach',
            'customer_phone' => '0900000099',
            'customer_email' => 'khach@example.com',
            'payments' => 0,
        ]);

        $mahang = $response->json('data.mahang');
        $this->assertNotEmpty($mahang);

        $this->assertDatabaseCount('donhang', 2);
        $this->assertDatabaseHas('donhang', [
            'product_id' => $productA->product_id,
            'soluong' => 2,
            'mahang' => $mahang,
        ]);
        $this->assertDatabaseHas('donhang', [
            'product_id' => $productB->product_id,
            'soluong' => 1,
            'mahang' => $mahang,
        ]);

        $this->assertDatabaseCount('giaodich', 2);
        $this->assertDatabaseHas('giaodich', [
            'sanpham_id' => $productA->product_id,
            'soluong' => 2,
            'magiaodich' => $mahang,
        ]);

        // checkout must empty the resolved cart
        $cart = $this->getJson('/api/cart');
        $this->assertCount(0, $cart->json('data'));
    }

    public function test_checkout_with_empty_cart_returns_422(): void
    {
        $response = $this->postJson('/api/checkout', [
            'name' => 'Nguyen Van Khach',
            'phone' => '0900000099',
            'email' => 'khach@example.com',
            'address' => '1 Le Loi, HCMC',
        ]);

        $response->assertStatus(422);
    }
}
