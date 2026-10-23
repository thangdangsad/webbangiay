<?php

namespace Tests\Feature;

use App\Models\CustomerAccount;
use App\Models\Order;
use App\Models\OrderCustomerInfo;
use App\Models\OrderTransaction;
use App\Models\ProductVariant;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrderTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(int $role = Staff::ROLE_ADMIN): Staff
    {
        $staff = Staff::factory()->create(['role' => $role]);
        $this->actingAs($staff, 'admin');

        return $staff;
    }

    public function test_guest_cannot_list_orders(): void
    {
        $this->getJson('/api/admin/orders')->assertStatus(401);
    }

    public function test_customer_guard_cannot_list_orders(): void
    {
        $customer = CustomerAccount::factory()->create();
        $this->actingAs($customer, 'customer');

        $this->getJson('/api/admin/orders')->assertStatus(401);
    }

    public function test_admin_can_list_orders(): void
    {
        $this->actingAsAdmin();
        $variant = ProductVariant::factory()->create();
        $customerInfo = OrderCustomerInfo::factory()->create();

        Order::factory()->create([
            'product_id' => $variant->variant_id,
            'customer_id' => $customerInfo->customer_id,
            'trangThai' => null,
        ]);
        Order::factory()->create(['trangThai' => 1]);
        Order::factory()->create(['trangThai' => 2]);

        $response = $this->getJson('/api/admin/orders');

        $response->assertOk();
        $this->assertCount(3, $response->json('data'));
    }

    public function test_admin_can_filter_orders_by_pending_status_including_null(): void
    {
        $this->actingAsAdmin();

        Order::factory()->create(['trangThai' => null]);
        Order::factory()->create(['trangThai' => 0]);
        Order::factory()->create(['trangThai' => 1]);
        Order::factory()->create(['trangThai' => 2]);

        $response = $this->getJson('/api/admin/orders?status=0');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    public function test_admin_can_filter_orders_by_confirmed_status(): void
    {
        $this->actingAsAdmin();

        Order::factory()->create(['trangThai' => null]);
        Order::factory()->create(['trangThai' => 1]);
        Order::factory()->create(['trangThai' => 2]);

        $response = $this->getJson('/api/admin/orders?status=1');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_admin_can_view_order_detail_with_customer_info_and_transactions(): void
    {
        $this->actingAsAdmin();

        $customerInfo = OrderCustomerInfo::factory()->create(['customer_name' => 'Nguyen Van Khach']);
        $order = Order::factory()->create(['customer_id' => $customerInfo->customer_id]);
        OrderTransaction::factory()->create(['khachhang_id' => $customerInfo->customer_id]);

        $response = $this->getJson('/api/admin/orders/'.$order->order_id);

        $response->assertOk();
        $response->assertJsonPath('data.order_id', $order->order_id);
        $response->assertJsonPath('data.customer_info.customer_name', 'Nguyen Van Khach');
        $this->assertCount(1, $response->json('data.customer_info.transactions'));
    }

    public function test_admin_can_confirm_an_order(): void
    {
        $this->actingAsAdmin();

        $customerInfo = OrderCustomerInfo::factory()->create();
        $order = Order::factory()->create(['customer_id' => $customerInfo->customer_id, 'trangThai' => null]);

        $response = $this->patchJson('/api/admin/orders/'.$order->order_id, [
            'trangThai' => 1,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.trangThai', 1);
        $this->assertDatabaseHas('donhang', [
            'order_id' => $order->order_id,
            'trangThai' => 1,
        ]);
    }

    public function test_confirming_an_order_updates_every_line_from_the_same_checkout(): void
    {
        $this->actingAsAdmin();

        $customerInfo = OrderCustomerInfo::factory()->create();
        $firstLine = Order::factory()->create(['customer_id' => $customerInfo->customer_id, 'trangThai' => null]);
        $secondLine = Order::factory()->create(['customer_id' => $customerInfo->customer_id, 'trangThai' => null]);

        $this->patchJson('/api/admin/orders/'.$firstLine->order_id, ['trangThai' => 2])->assertOk();

        $this->assertDatabaseHas('donhang', ['order_id' => $firstLine->order_id, 'trangThai' => 2]);
        $this->assertDatabaseHas('donhang', ['order_id' => $secondLine->order_id, 'trangThai' => 2]);
    }

    public function test_order_status_update_rejects_unknown_status(): void
    {
        $this->actingAsAdmin();
        $order = Order::factory()->create();

        $response = $this->patchJson('/api/admin/orders/'.$order->order_id, [
            'trangThai' => 9,
        ]);

        $response->assertStatus(422);
    }

    public function test_customer_guard_cannot_view_order_detail(): void
    {
        $customer = CustomerAccount::factory()->create();
        $this->actingAs($customer, 'customer');

        $order = Order::factory()->create();

        $this->getJson('/api/admin/orders/'.$order->order_id)->assertStatus(401);
    }
}
