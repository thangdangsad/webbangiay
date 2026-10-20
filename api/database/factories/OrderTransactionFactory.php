<?php

namespace Database\Factories;

use App\Models\OrderCustomerInfo;
use App\Models\OrderTransaction;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderTransaction>
 */
class OrderTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'khachhang_id' => OrderCustomerInfo::factory(),
            'sanpham_id' => Product::factory(),
            'soluong' => fake()->numberBetween(1, 5),
            'magiaodich' => (string) fake()->numberBetween(1000, 9999),
            'ngayThangNam' => fake()->date(),
        ];
    }
}
