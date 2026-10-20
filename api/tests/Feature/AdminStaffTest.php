<?php

namespace Tests\Feature;

use App\Models\CustomerAccount;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminStaffTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): Staff
    {
        $staff = Staff::factory()->create(['role' => Staff::ROLE_ADMIN]);
        $this->actingAs($staff, 'admin');

        return $staff;
    }

    private function actingAsStaff(): Staff
    {
        $staff = Staff::factory()->create(['role' => Staff::ROLE_STAFF]);
        $this->actingAs($staff, 'admin');

        return $staff;
    }

    public function test_guest_cannot_list_staff(): void
    {
        $this->getJson('/api/admin/staff')->assertStatus(401);
    }

    public function test_customer_guard_cannot_list_staff(): void
    {
        $customer = CustomerAccount::factory()->create();
        $this->actingAs($customer, 'customer');

        $this->getJson('/api/admin/staff')->assertStatus(401);
    }

    public function test_any_authenticated_admin_or_staff_can_list_staff(): void
    {
        $this->actingAsStaff();
        Staff::factory()->count(2)->create();

        $response = $this->getJson('/api/admin/staff');

        $response->assertOk();
        $this->assertCount(3, $response->json('data'));
    }

    public function test_admin_can_create_staff_account_with_hashed_password(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/admin/staff', [
            'user_name' => 'Nhan Vien Moi',
            'user_email' => 'staff-new@example.com',
            'user_phone' => '0900001111',
            'user_address' => 'HQ',
            'password' => 'secret123',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.role', Staff::ROLE_STAFF);

        $created = Staff::where('user_email', 'staff-new@example.com')->first();
        $this->assertNotNull($created);
        $this->assertTrue(Hash::check('secret123', $created->user_password));
        // never the legacy unsalted md5 of the password
        $this->assertNotSame(md5('secret123'), $created->user_password);
    }

    public function test_admin_can_create_another_admin_account(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/admin/staff', [
            'user_name' => 'Admin Moi',
            'user_email' => 'admin-new@example.com',
            'password' => 'secret123',
            'role' => Staff::ROLE_ADMIN,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.role', Staff::ROLE_ADMIN);
    }

    public function test_plain_staff_cannot_create_staff_account(): void
    {
        $this->actingAsStaff();

        $response = $this->postJson('/api/admin/staff', [
            'user_name' => 'Nope',
            'user_email' => 'nope@example.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('quanly_taikhoan', ['user_email' => 'nope@example.com']);
    }

    public function test_staff_creation_requires_unique_email(): void
    {
        $this->actingAsAdmin();
        Staff::factory()->create(['user_email' => 'dup@example.com']);

        $response = $this->postJson('/api/admin/staff', [
            'user_name' => 'Dup',
            'user_email' => 'dup@example.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(422);
    }

    public function test_staff_can_edit_their_own_profile(): void
    {
        $staff = $this->actingAsStaff();

        $response = $this->patchJson('/api/admin/staff/'.$staff->user_id, [
            'user_name' => 'Ten Moi',
            'user_phone' => '0909999999',
            'user_address' => 'Dia chi moi',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('quanly_taikhoan', [
            'user_id' => $staff->user_id,
            'user_name' => 'Ten Moi',
        ]);
    }

    public function test_staff_cannot_edit_another_staff_account(): void
    {
        $this->actingAsStaff();
        $other = Staff::factory()->create();

        $response = $this->patchJson('/api/admin/staff/'.$other->user_id, [
            'user_name' => 'Hacked',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_edit_another_staff_account(): void
    {
        $this->actingAsAdmin();
        $other = Staff::factory()->create();

        $response = $this->patchJson('/api/admin/staff/'.$other->user_id, [
            'user_name' => 'Edited By Admin',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('quanly_taikhoan', [
            'user_id' => $other->user_id,
            'user_name' => 'Edited By Admin',
        ]);
    }

    public function test_staff_can_change_their_own_password_with_correct_current_password(): void
    {
        $staff = Staff::factory()->create(['user_password' => Hash::make('oldpass123')]);
        $this->actingAs($staff, 'admin');

        $response = $this->patchJson('/api/admin/staff/'.$staff->user_id.'/password', [
            'current_password' => 'oldpass123',
            'password' => 'newpass123',
            'password_confirmation' => 'newpass123',
        ]);

        $response->assertOk();
        $this->assertTrue(Hash::check('newpass123', $staff->refresh()->user_password));
    }

    public function test_changing_own_password_rejects_wrong_current_password(): void
    {
        $staff = Staff::factory()->create(['user_password' => Hash::make('oldpass123')]);
        $this->actingAs($staff, 'admin');

        $response = $this->patchJson('/api/admin/staff/'.$staff->user_id.'/password', [
            'current_password' => 'wrong-password',
            'password' => 'newpass123',
            'password_confirmation' => 'newpass123',
        ]);

        $response->assertStatus(422);
    }

    public function test_admin_can_reset_another_staffs_password_without_current_password(): void
    {
        $this->actingAsAdmin();
        $other = Staff::factory()->create(['user_password' => Hash::make('oldpass123')]);

        $response = $this->patchJson('/api/admin/staff/'.$other->user_id.'/password', [
            'password' => 'resetpass123',
            'password_confirmation' => 'resetpass123',
        ]);

        $response->assertOk();
        $this->assertTrue(Hash::check('resetpass123', $other->refresh()->user_password));
    }

    public function test_plain_staff_cannot_reset_another_staffs_password(): void
    {
        $this->actingAsStaff();
        $other = Staff::factory()->create();

        $response = $this->patchJson('/api/admin/staff/'.$other->user_id.'/password', [
            'password' => 'resetpass123',
            'password_confirmation' => 'resetpass123',
        ]);

        $response->assertStatus(403);
    }

    public function test_customer_guard_cannot_create_staff(): void
    {
        $customer = CustomerAccount::factory()->create();
        $this->actingAs($customer, 'customer');

        $this->postJson('/api/admin/staff', [
            'user_name' => 'Nope',
            'user_email' => 'nope2@example.com',
            'password' => 'secret123',
        ])->assertStatus(401);
    }

    public function test_staff_deletion_is_not_supported(): void
    {
        $this->actingAsAdmin();
        $other = Staff::factory()->create();

        $this->deleteJson('/api/admin/staff/'.$other->user_id)->assertStatus(405);
    }
}
