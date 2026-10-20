<?php

namespace Tests\Feature;

use App\Models\CustomerAccount;
use App\Models\OrderCustomerInfo;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCustomerTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): Staff
    {
        $staff = Staff::factory()->create();
        $this->actingAs($staff, 'admin');

        return $staff;
    }

    public function test_guest_cannot_list_customers(): void
    {
        $this->getJson('/api/admin/customers')->assertStatus(401);
    }

    public function test_customer_guard_cannot_list_customers(): void
    {
        $customer = CustomerAccount::factory()->create();
        $this->actingAs($customer, 'customer');

        $this->getJson('/api/admin/customers')->assertStatus(401);
    }

    public function test_admin_can_list_customer_accounts(): void
    {
        $this->actingAsAdmin();
        CustomerAccount::factory()->count(3)->create();

        $response = $this->getJson('/api/admin/customers');

        $response->assertOk();
        $this->assertCount(3, $response->json('data'));
    }

    public function test_admin_can_view_customer_detail_with_order_history(): void
    {
        $this->actingAsAdmin();

        $customer = CustomerAccount::factory()->create(['user_code' => '4321']);
        OrderCustomerInfo::factory()->create(['user_code' => '4321']);
        OrderCustomerInfo::factory()->create(['user_code' => '4321']);
        OrderCustomerInfo::factory()->create(['user_code' => '9999']); // unrelated

        $response = $this->getJson('/api/admin/customers/'.$customer->user_id);

        $response->assertOk();
        $response->assertJsonPath('data.user_id', $customer->user_id);
        $this->assertCount(2, $response->json('order_history'));
    }

    public function test_admin_sees_empty_order_history_for_customer_who_never_checked_out(): void
    {
        $this->actingAsAdmin();

        $customer = CustomerAccount::factory()->create(['user_code' => 0]);

        $response = $this->getJson('/api/admin/customers/'.$customer->user_id);

        $response->assertOk();
        $this->assertSame([], $response->json('order_history'));
    }

    public function test_admin_cannot_create_customer_account(): void
    {
        $this->actingAsAdmin();

        // No write endpoint exists for customer accounts (legacy is read-only here).
        $response = $this->postJson('/api/admin/customers', [
            'user_name' => 'Should not work',
            'user_email' => 'nope@example.com',
        ]);

        $response->assertStatus(405);
    }
}
