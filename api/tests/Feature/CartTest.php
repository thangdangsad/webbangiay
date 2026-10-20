<?php

namespace Tests\Feature;

use App\Models\CustomerAccount;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\InteractsWithStatefulSession;
use Tests\TestCase;

/**
 * Regression coverage for the legacy "one global cart for everyone" bug
 * (frontend/cart.php had no scoping at all). Every cart row must now be
 * scoped to either the authenticated customer or a session-bound guest
 * token (see App\Services\CartResolver).
 */
class CartTest extends TestCase
{
    use InteractsWithStatefulSession, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsStatefulSpa();
    }

    /**
     * Forget any carried-over session cookie and in-memory session
     * attributes, simulating a brand new guest visiting with a fresh
     * browser session. The attribute flush is needed on top of dropping
     * the cookie because the testing HTTP client reuses the same session
     * store instance across simulated requests within a single test, and
     * Store::loadSession() merges onto existing attributes rather than
     * resetting them — a harmless testing-only artifact (a real browser's
     * next request is a fresh PHP process), but one that would otherwise
     * leak guest A's session data into guest B's "fresh" session here.
     */
    private function startFreshGuestSession(): void
    {
        $this->defaultCookies = [];
        $this->unencryptedCookies = [];
        $this->app['session']->flush();
    }

    public function test_guest_can_add_update_and_remove_cart_items(): void
    {
        $product = Product::factory()->create(['product_price' => 100000, 'product_discount' => 90000]);

        $add = $this->postJson('/api/cart/items', [
            'product_id' => $product->product_id,
            'quantity' => 2,
        ]);
        $add->assertCreated();
        $this->continueSessionFrom($add);

        $index = $this->getJson('/api/cart');
        $index->assertOk();
        $this->assertCount(1, $index->json('data'));
        $cartId = $index->json('data.0.cart_id');
        $this->assertSame(2, $index->json('data.0.product_quantity'));
        $this->assertEquals(90000, $index->json('data.0.product_price'));

        // adding the same product again increments quantity rather than
        // creating a second row (matches legacy `themgiohang` behavior)
        $addAgain = $this->postJson('/api/cart/items', [
            'product_id' => $product->product_id,
            'quantity' => 1,
        ]);
        $addAgain->assertOk();
        $this->assertCount(1, $this->getJson('/api/cart')->json('data'));
        $this->assertSame(3, $this->getJson('/api/cart')->json('data.0.product_quantity'));

        $update = $this->patchJson('/api/cart/items/'.$cartId, ['quantity' => 5]);
        $update->assertOk();
        $this->assertSame(5, $this->getJson('/api/cart')->json('data.0.product_quantity'));

        // a quantity of zero removes the line, matching legacy capnhatsoluong
        $this->patchJson('/api/cart/items/'.$cartId, ['quantity' => 0])->assertNoContent();
        $this->assertCount(0, $this->getJson('/api/cart')->json('data'));
    }

    public function test_guest_cart_removal_endpoint_deletes_the_line(): void
    {
        $product = Product::factory()->create();

        $add = $this->postJson('/api/cart/items', ['product_id' => $product->product_id]);
        $this->continueSessionFrom($add);
        $cartId = $this->getJson('/api/cart')->json('data.0.cart_id');

        $this->deleteJson('/api/cart/items/'.$cartId)->assertNoContent();
        $this->assertCount(0, $this->getJson('/api/cart')->json('data'));
    }

    public function test_two_guest_sessions_have_isolated_carts(): void
    {
        $productA = Product::factory()->create();
        $productB = Product::factory()->create();

        // guest A
        $addA = $this->postJson('/api/cart/items', ['product_id' => $productA->product_id]);
        $addA->assertCreated();
        $this->continueSessionFrom($addA);
        $cartA = $this->getJson('/api/cart');
        $cartA->assertOk();
        $this->assertCount(1, $cartA->json('data'));
        $this->assertSame($productA->product_id, $cartA->json('data.0.product_id'));

        // a brand new guest, with no session cookie carried over
        $this->startFreshGuestSession();

        $addB = $this->postJson('/api/cart/items', ['product_id' => $productB->product_id]);
        $addB->assertCreated();
        $this->continueSessionFrom($addB);
        $cartB = $this->getJson('/api/cart');
        $cartB->assertOk();

        // guest B only sees their own item, never guest A's
        $this->assertCount(1, $cartB->json('data'));
        $this->assertSame($productB->product_id, $cartB->json('data.0.product_id'));
    }

    public function test_authenticated_customer_cart_is_scoped_to_that_customer(): void
    {
        $customerA = CustomerAccount::factory()->create();
        $customerB = CustomerAccount::factory()->create();
        $productA = Product::factory()->create();
        $productB = Product::factory()->create();

        $this->actingAs($customerA, 'customer');
        $this->postJson('/api/cart/items', ['product_id' => $productA->product_id])->assertCreated();
        $cartA = $this->getJson('/api/cart');
        $this->assertCount(1, $cartA->json('data'));
        $this->assertSame($productA->product_id, $cartA->json('data.0.product_id'));
        $this->assertSame($customerA->user_id, $cartA->json('data.0.customer_id'));

        $this->actingAs($customerB, 'customer');
        $this->postJson('/api/cart/items', ['product_id' => $productB->product_id])->assertCreated();
        $cartB = $this->getJson('/api/cart');

        // customer B only sees their own cart, never customer A's
        $this->assertCount(1, $cartB->json('data'));
        $this->assertSame($productB->product_id, $cartB->json('data.0.product_id'));
    }

    public function test_guest_cart_merges_into_account_on_login(): void
    {
        $product = Product::factory()->create();
        $customer = CustomerAccount::factory()->create([
            'user_email' => 'merge-test@example.com',
            'user_password' => Hash::make('secret123'),
        ]);

        $add = $this->postJson('/api/cart/items', ['product_id' => $product->product_id]);
        $add->assertCreated();
        $this->continueSessionFrom($add);

        $this->assertDatabaseHas('cart', [
            'product_id' => $product->product_id,
            'customer_id' => null,
        ]);

        $login = $this->postJson('/api/login', [
            'email' => 'merge-test@example.com',
            'password' => 'secret123',
        ]);
        $login->assertOk();
        $this->continueSessionFrom($login);

        $this->assertDatabaseHas('cart', [
            'product_id' => $product->product_id,
            'customer_id' => $customer->user_id,
            'guest_token' => null,
        ]);

        $cart = $this->getJson('/api/cart');
        $cart->assertOk();
        $this->assertCount(1, $cart->json('data'));
        $this->assertSame($product->product_id, $cart->json('data.0.product_id'));
    }
}
