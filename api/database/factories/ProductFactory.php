<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_name' => fake()->words(3, true),
            'product_image' => fake()->lexify('????????').'.jpg',
            'product_price' => fake()->numberBetween(100000, 2000000),
            'product_discount' => fake()->numberBetween(50000, 1800000),
            'product_details' => fake()->sentence(),
            'product_description' => fake()->paragraph(),
            'category_id' => Category::factory(),
        ];
    }
}
