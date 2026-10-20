<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_index_lists_categories(): void
    {
        Category::factory()->count(3)->create();

        $response = $this->getJson('/api/categories');

        $response->assertOk();
        $this->assertCount(3, $response->json('data'));
    }
}
