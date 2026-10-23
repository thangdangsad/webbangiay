<?php

namespace Database\Factories;

use App\Models\Cart;
use App\Models\ProductVariant;
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
            'variant_id' => ProductVariant::factory(),
            'product_name' => fake()->words(3, true),
            'product_price' => fake()->numberBetween(100000, 2000000),
            'product_image' => fake()->lexify('????????').'.jpg',
            'product_quantity' => fake()->numberBetween(1, 5),
            'size' => fake()->randomElement(['38', '39', '40', '41', '42', '43', '44']),
            'customer_id' => null,
            'guest_token' => (string) Str::uuid(),
        ];
    }
}
