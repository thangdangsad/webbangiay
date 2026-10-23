<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
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
            'product_price' => 500000,
            'category_id' => $category->category_id,
            'variants' => [
                ['size' => '40', 'product_quantity' => 10],
            ],
        ]);

        $response->assertStatus(401);
    }

    public function test_admin_can_create_product(): void
    {
        $this->actingAsAdmin();
        $category = Category::factory()->create();

        $response = $this->postJson('/api/admin/products', [
            'product_name' => 'Giày chạy bộ',
            'product_price' => 500000,
            'product_discount' => 450000,
            'product_details' => 'Chi tiết',
            'product_description' => 'Mô tả',
            'category_id' => $category->category_id,
            'variants' => [
                ['size' => '40', 'product_quantity' => 10],
            ],
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('product', [
            'product_name' => 'Giày chạy bộ',
            'category_id' => $category->category_id,
        ]);
        $this->assertDatabaseHas('product_variant', [
            'size' => '40',
            'product_quantity' => 10,
            'is_active' => true,
        ]);
    }

    public function test_admin_creating_a_product_persists_every_posted_variant(): void
    {
        $this->actingAsAdmin();
        $category = Category::factory()->create();

        $response = $this->postJson('/api/admin/products', [
            'product_name' => 'Giày chạy bộ',
            'product_price' => 500000,
            'category_id' => $category->category_id,
            'variants' => [
                ['size' => '40', 'product_quantity' => 10],
                ['size' => '42', 'product_quantity' => 5],
            ],
        ]);

        $response->assertCreated();
        $productId = $response->json('data.product_id');

        $this->assertDatabaseCount('product_variant', 2);
        $this->assertDatabaseHas('product_variant', [
            'product_id' => $productId,
            'size' => '40',
            'product_quantity' => 10,
        ]);
        $this->assertDatabaseHas('product_variant', [
            'product_id' => $productId,
            'size' => '42',
            'product_quantity' => 5,
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
            'product_price' => 500000,
            'category_id' => $category->category_id,
            'image' => $file,
            'variants' => [
                ['size' => '40', 'product_quantity' => 10],
            ],
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
        $variant = ProductVariant::factory()->create(['product_id' => $product->product_id, 'product_quantity' => 3]);

        $response = $this->putJson('/api/admin/products/'.$product->product_id, [
            'product_name' => 'Tên mới',
            'product_price' => 600000,
            'category_id' => $category->category_id,
            'variants' => [
                ['variant_id' => $variant->variant_id, 'size' => $variant->size, 'product_quantity' => 20],
            ],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('product', [
            'product_id' => $product->product_id,
            'product_name' => 'Tên mới',
        ]);
        $this->assertDatabaseHas('product_variant', [
            'variant_id' => $variant->variant_id,
            'product_quantity' => 20,
        ]);
    }

    /**
     * Decision #2: deleting a variant that has (or might have) order
     * history must never hard-delete it. The simplest uniform rule the
     * admin edit form follows is to always soft-deactivate an existing
     * variant that gets omitted from the posted form, rather than deleting
     * its row.
     */
    public function test_updating_a_product_and_omitting_an_existing_variant_deactivates_it_instead_of_deleting_it(): void
    {
        $this->actingAsAdmin();
        $product = Product::factory()->create();
        $kept = ProductVariant::factory()->create(['product_id' => $product->product_id, 'size' => '40']);
        $omitted = ProductVariant::factory()->create(['product_id' => $product->product_id, 'size' => '42']);

        $response = $this->putJson('/api/admin/products/'.$product->product_id, [
            'product_name' => $product->product_name,
            'product_price' => $product->product_price,
            'variants' => [
                ['variant_id' => $kept->variant_id, 'size' => $kept->size, 'product_quantity' => $kept->product_quantity],
            ],
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('product_variant', [
            'variant_id' => $kept->variant_id,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('product_variant', [
            'variant_id' => $omitted->variant_id,
            'is_active' => false,
        ]);
    }

    public function test_admin_product_endpoints_include_inactive_variants(): void
    {
        $this->actingAsAdmin();
        $product = Product::factory()->create();
        ProductVariant::factory()->create(['product_id' => $product->product_id, 'size' => '40', 'is_active' => true]);
        $deactivated = ProductVariant::factory()->create(['product_id' => $product->product_id, 'size' => '42', 'is_active' => false]);

        // The public catalog never shows a deactivated size...
        $publicShow = $this->getJson('/api/products/'.$product->product_id);
        $publicShow->assertOk();
        $this->assertNotContains(
            $deactivated->variant_id,
            array_column($publicShow->json('data.variants'), 'variant_id'),
        );

        // ...but the admin endpoints must, so a discontinued size can still
        // be seen and reactivated from the edit form.
        $adminIndex = $this->getJson('/api/admin/products');
        $adminIndex->assertOk();
        $indexedProduct = collect($adminIndex->json('data'))->firstWhere('product_id', $product->product_id);
        $this->assertContains($deactivated->variant_id, array_column($indexedProduct['variants'], 'variant_id'));

        $adminShow = $this->getJson('/api/admin/products/'.$product->product_id);
        $adminShow->assertOk();
        $this->assertContains(
            $deactivated->variant_id,
            array_column($adminShow->json('data.variants'), 'variant_id'),
        );
    }

    public function test_re_posting_a_previously_deactivated_variant_id_reactivates_it(): void
    {
        $this->actingAsAdmin();
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->product_id,
            'size' => '42',
            'is_active' => false,
        ]);

        $response = $this->putJson('/api/admin/products/'.$product->product_id, [
            'product_name' => $product->product_name,
            'product_price' => $product->product_price,
            'variants' => [
                ['variant_id' => $variant->variant_id, 'size' => $variant->size, 'product_quantity' => 5],
            ],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('product_variant', [
            'variant_id' => $variant->variant_id,
            'is_active' => true,
            'product_quantity' => 5,
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
