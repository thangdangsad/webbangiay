<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminProductTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): Staff
    {
        $staff = Staff::factory()->create();
        $this->actingAs($staff, 'admin');

        return $staff;
    }

    public function test_guest_cannot_create_product(): void
    {
        $category = Category::factory()->create();

        $response = $this->postJson('/api/admin/products', [
            'product_name' => 'Giày chạy bộ',
            'product_quantity' => 10,
            'product_price' => 500000,
            'category_id' => $category->category_id,
        ]);

        $response->assertStatus(401);
    }

    public function test_admin_can_create_product(): void
    {
        $this->actingAsAdmin();
        $category = Category::factory()->create();

        $response = $this->postJson('/api/admin/products', [
            'product_name' => 'Giày chạy bộ',
            'size' => '40',
            'product_quantity' => 10,
            'product_price' => 500000,
            'product_discount' => 450000,
            'product_details' => 'Chi tiết',
            'product_description' => 'Mô tả',
            'category_id' => $category->category_id,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('product', [
            'product_name' => 'Giày chạy bộ',
            'category_id' => $category->category_id,
        ]);
    }

    public function test_admin_product_image_upload_gets_a_generated_filename(): void
    {
        Storage::fake('public');
        $this->actingAsAdmin();
        $category = Category::factory()->create();

        $maliciousFilename = '../../../etc/passwd.jpg';
        $file = UploadedFile::fake()->image($maliciousFilename);

        $response = $this->post('/api/admin/products', [
            'product_name' => 'Giày chạy bộ',
            'product_quantity' => 10,
            'product_price' => 500000,
            'category_id' => $category->category_id,
            'image' => $file,
        ], ['Accept' => 'application/json']);

        $response->assertCreated();

        $storedFilename = $response->json('data.product_image');

        $this->assertNotNull($storedFilename);
        $this->assertNotSame($maliciousFilename, $storedFilename);
        $this->assertStringNotContainsString('..', $storedFilename);
        $this->assertStringNotContainsString('passwd', $storedFilename);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\.\w+$/',
            $storedFilename,
        );
        Storage::disk('public')->assertExists('products/'.$storedFilename);
    }

    public function test_admin_can_update_product(): void
    {
        $this->actingAsAdmin();
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->category_id]);

        $response = $this->putJson('/api/admin/products/'.$product->product_id, [
            'product_name' => 'Tên mới',
            'product_quantity' => 20,
            'product_price' => 600000,
            'category_id' => $category->category_id,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('product', [
            'product_id' => $product->product_id,
            'product_name' => 'Tên mới',
            'product_quantity' => 20,
        ]);
    }

    public function test_admin_can_delete_product(): void
    {
        $this->actingAsAdmin();
        $product = Product::factory()->create();

        $response = $this->deleteJson('/api/admin/products/'.$product->product_id);

        $response->assertNoContent();
        $this->assertDatabaseMissing('product', ['product_id' => $product->product_id]);
    }
}
