<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderCustomerInfo;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
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
            'customer_id' => OrderCustomerInfo::factory(),
            'soluong' => fake()->numberBetween(1, 5),
            'tongDoanhThu' => fake()->numberBetween(100000, 5000000),
            'mahang' => (string) fake()->numberBetween(1000, 9999),
            'ngayDatHang' => fake()->date(),
            'trangThai' => null,
            'payments' => 0,
        ];
    }
}
