<?php

namespace Database\Factories;

use App\Models\Staff;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Staff>
 */
class StaffFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_name' => fake()->name(),
            'user_code' => (string) fake()->numberBetween(1000, 9999),
            'user_email' => fake()->unique()->safeEmail(),
            'user_phone' => fake()->phoneNumber(),
            'user_address' => fake()->address(),
            'user_password' => Hash::make('password'),
            'role' => Staff::ROLE_ADMIN,
        ];
    }
}
