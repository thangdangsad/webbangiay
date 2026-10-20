<?php

namespace Database\Factories;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Cart>
 */
class CartFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'product_name' => fake()->words(3, true),
            'product_price' => fake()->numberBetween(100000, 2000000),
            'product_image' => fake()->lexify('????????').'.jpg',
            'product_quantity' => fake()->numberBetween(1, 5),
            'customer_id' => null,
            'guest_token' => (string) Str::uuid(),
        ];
    }
}
