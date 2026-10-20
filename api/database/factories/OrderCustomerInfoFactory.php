<?php

namespace Database\Factories;

use App\Models\OrderCustomerInfo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderCustomerInfo>
 */
class OrderCustomerInfoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_name' => fake()->name(),
            'user_code' => fake()->numberBetween(1000, 9999),
            'customer_phone' => fake()->phoneNumber(),
            'customer_email' => fake()->safeEmail(),
            'customer_address' => fake()->address(),
            'payments' => 0,
            'customer_note' => fake()->optional()->sentence(),
        ];
    }
}
