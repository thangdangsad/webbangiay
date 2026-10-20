<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCategoryTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): Staff
    {
        $staff = Staff::factory()->create();
        $this->actingAs($staff, 'admin');

        return $staff;
    }

    public function test_guest_cannot_create_category(): void
    {
        $response = $this->postJson('/api/admin/categories', [
            'category_name' => 'Giày thể thao',
        ]);

        $response->assertStatus(401);
    }

    public function test_admin_can_create_category(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/admin/categories', [
            'category_name' => 'Giày thể thao',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('tbl_category', ['category_name' => 'Giày thể thao']);
    }

    public function test_admin_can_update_category(): void
    {
        $this->actingAsAdmin();
        $category = Category::factory()->create(['category_name' => 'Cũ']);

        $response = $this->putJson('/api/admin/categories/'.$category->category_id, [
            'category_name' => 'Mới',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('tbl_category', [
            'category_id' => $category->category_id,
            'category_name' => 'Mới',
        ]);
    }

    public function test_admin_can_delete_category(): void
    {
        $this->actingAsAdmin();
        $category = Category::factory()->create();

        $response = $this->deleteJson('/api/admin/categories/'.$category->category_id);

        $response->assertNoContent();
        $this->assertDatabaseMissing('tbl_category', ['category_id' => $category->category_id]);
    }
}
