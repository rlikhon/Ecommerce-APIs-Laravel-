<?php

namespace Database\Factories;

use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => 1,
            'product_id' => Product::factory(),
            'name' => $this->faker->word(),
            'quantity' => $this->faker->numberBetween(1, 5),
            'unit_price' => $this->faker->randomFloat(2, 5, 500),
            'price' => $this->faker->randomFloat(2, 10, 2000),
            'size' => $this->faker->randomElement(['S', 'M', 'L', 'XL', null]),
        ];
    }
}
