<?php

namespace Database\Factories;

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
            'title' => $this->faker->word(),
            'price' => $this->faker->randomFloat(2, 10, 1000),
            'compare_price' => $this->faker->randomFloat(2, 1000, 2000),
            'description' => $this->faker->sentence(),
            'short_description' => $this->faker->sentence(),
            'category_id' => \App\Models\Category::factory(),
            'brand_id' => \App\Models\Brand::factory(),
            'qty' => $this->faker->numberBetween(1, 100),
            'sku' => $this->faker->boolean(),
            'barcode' => $this->faker->unique()->ean13(),
            'status' => 1,
            'is_featured' => 'no',
        ];
    }
}
