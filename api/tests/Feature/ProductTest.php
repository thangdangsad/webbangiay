<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_index_lists_products(): void
    {
        Product::factory()->count(3)->create();

        $response = $this->getJson('/api/products');

        $response->assertOk();
        $this->assertCount(3, $response->json('data'));
    }

    public function test_product_index_filters_by_category(): void
    {
        $categoryA = Category::factory()->create();
        $categoryB = Category::factory()->create();

        Product::factory()->count(2)->create(['category_id' => $categoryA->category_id]);
        Product::factory()->count(3)->create(['category_id' => $categoryB->category_id]);

        $response = $this->getJson('/api/products?category_id='.$categoryA->category_id);

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
        foreach ($response->json('data') as $product) {
            $this->assertSame($categoryA->category_id, $product['category_id']);
        }
    }

    public function test_product_index_searches_by_name(): void
    {
        Product::factory()->create(['product_name' => 'Giày thể thao Nike']);
        Product::factory()->create(['product_name' => 'Áo sơ mi']);

        $response = $this->getJson('/api/products?q=Nike');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('Giày thể thao Nike', $response->json('data.0.product_name'));
    }

    /**
     * Per decision #3: storefront exposes exact stock quantity per size, not
     * just an in-stock/out-of-stock boolean, and only active variants.
     */
    public function test_product_index_includes_active_variants_with_exact_quantity(): void
    {
        $product = Product::factory()->create();
        ProductVariant::factory()->create([
            'product_id' => $product->product_id,
            'size' => '41',
            'product_quantity' => 7,
            'is_active' => true,
        ]);
        ProductVariant::factory()->create([
            'product_id' => $product->product_id,
            'size' => '42',
            'product_quantity' => 0,
            'is_active' => false,
        ]);

        $response = $this->getJson('/api/products');

        $response->assertOk();
        $variants = $response->json('data.0.variants');
        $this->assertCount(1, $variants);
        $this->assertSame('41', $variants[0]['size']);
        $this->assertSame(7, $variants[0]['product_quantity']);
    }

    public function test_product_show_returns_detail_with_category(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->category_id]);
        $variant = ProductVariant::factory()->create(['product_id' => $product->product_id]);

        $response = $this->getJson('/api/products/'.$product->product_id);

        $response->assertOk();
        $response->assertJsonPath('data.product_id', $product->product_id);
        $response->assertJsonPath('data.category.category_id', $category->category_id);
        $this->assertCount(1, $response->json('data.variants'));
        $response->assertJsonPath('data.variants.0.variant_id', $variant->variant_id);
    }

    public function test_product_similar_returns_same_category_excluding_self(): void
    {
        $category = Category::factory()->create();
        $otherCategory = Category::factory()->create();

        $product = Product::factory()->create(['category_id' => $category->category_id]);
        $sameCategoryProducts = Product::factory()->count(5)->create(['category_id' => $category->category_id]);
        Product::factory()->count(2)->create(['category_id' => $otherCategory->category_id]);

        $response = $this->getJson('/api/products/'.$product->product_id.'/similar');

        $response->assertOk();
        $data = $response->json('data');

        // limited to 4, same category, excludes the product itself
        $this->assertCount(4, $data);
        foreach ($data as $item) {
            $this->assertSame($category->category_id, $item['category_id']);
            $this->assertNotSame($product->product_id, $item['product_id']);
        }
    }
}
