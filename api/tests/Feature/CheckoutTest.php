<?php

namespace Tests\Feature;

use App\Models\CustomerAccount;
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

    public function test_cod_checkout_decrements_product_stock(): void
    {
        $customer = CustomerAccount::factory()->create();
        $this->actingAs($customer, 'customer');

        $product = Product::factory()->create(['product_quantity' => 5]);

        $this->postJson('/api/cart/items', ['product_id' => $product->product_id, 'quantity' => 2])
            ->assertCreated();

        $this->postJson('/api/checkout', $this->shippingPayload())->assertCreated();

        $this->assertSame(3, $product->refresh()->product_quantity);
    }

    public function test_checkout_rejects_cart_quantity_above_available_stock(): void
    {
        $customer = CustomerAccount::factory()->create();
        $this->actingAs($customer, 'customer');

        $product = Product::factory()->create(['product_quantity' => 1]);

        $this->postJson('/api/cart/items', ['product_id' => $product->product_id, 'quantity' => 2])
            ->assertCreated();

        $response = $this->postJson('/api/checkout', $this->shippingPayload());

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('cart');
        // the failed checkout must not have written any order rows or
        // touched stock — the whole reserveStock()+order-write transaction
        // rolled back together.
        $this->assertDatabaseCount('donhang', 0);
        $this->assertSame(1, $product->refresh()->product_quantity);
    }

    /**
     * Two different customers checking out the last unit of the same
     * product "at the same time" is the actual race this guards against:
     * without a DB-level row lock, both requests could read "1 left" before
     * either writes, and both would succeed — overselling it. A single
     * PHPUnit process can't literally run two HTTP requests in parallel,
     * but it can still prove the property that matters sequentially: once
     * the first customer's checkout transaction commits (stock now 0), a
     * second customer checking out the same product must see the
     * post-commit state and fail — it can never also succeed against the
     * same unit. OrderService::reserveStock()'s `lockForUpdate` is what
     * makes this hold under real concurrency too, by blocking the second
     * transaction on that product row until the first one finishes instead
     * of letting it read a stale quantity.
     */
    public function test_second_customer_checking_out_the_last_unit_fails_after_first_succeeds(): void
    {
        $product = Product::factory()->create(['product_quantity' => 1]);

        $firstCustomer = CustomerAccount::factory()->create();
        $this->actingAs($firstCustomer, 'customer');
        $this->postJson('/api/cart/items', ['product_id' => $product->product_id, 'quantity' => 1])
            ->assertCreated();
        $this->postJson('/api/checkout', $this->shippingPayload())->assertCreated();

        $this->assertSame(0, $product->refresh()->product_quantity);

        $secondCustomer = CustomerAccount::factory()->create();
        $this->actingAs($secondCustomer, 'customer');
        $this->postJson('/api/cart/items', ['product_id' => $product->product_id, 'quantity' => 1])
            ->assertCreated();
        $second = $this->postJson('/api/checkout', $this->shippingPayload());

        $second->assertStatus(422);
        $second->assertJsonValidationErrors('cart');
    }

    /**
     * @return array<string, string>
     */
    private function shippingPayload(): array
    {
        return [
            'name' => 'Nguyen Van Khach',
            'phone' => '0900000099',
            'email' => 'khach@example.com',
            'address' => '1 Le Loi, HCMC',
            'payments' => '0',
        ];
    }
}
