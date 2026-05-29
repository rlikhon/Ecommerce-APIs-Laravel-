<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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
            'user_id' => User::factory(),
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'address' => $this->faker->address(),
            'mobile' => $this->faker->phoneNumber(),
            'state' => $this->faker->state(),
            'zip' => $this->faker->postcode(),
            'city' => $this->faker->city(),
            'grand_total' => $this->faker->randomFloat(2, 10, 1000),
            'sub_total' => $this->faker->randomFloat(2, 10, 900),
            'discount' => $this->faker->randomFloat(2, 0, 100),
            'shipping' => $this->faker->randomFloat(2, 0, 50),
            'payment_method' => $this->faker->randomElement(['credit_card', 'debit_card', 'paypal', 'bank_transfer', 'cash_on_delivery']),
            'payment_status' => $this->faker->randomElement(['pending', 'completed', 'failed']),
            'status' => OrderStatus::Pending->value,
            'confirmation_number' => 'ORD-'.strtoupper(Str::random(4)).'-'.date('YmdHis').'-'.random_int(1000, 9999),
            'processed_at' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Order $order) {
            OrderLog::create([
                'order_id' => $order->id,
                'action' => 'created',
                'old_status' => null,
                'new_status' => OrderStatus::Pending->value,
                'description' => 'Order created via factory',
                'user_id' => null,
                'metadata' => [],
            ]);
        });
    }

    public function confirmed(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => OrderStatus::Confirmed->value,
                'processed_at' => now(),
            ];
        });
    }

    public function shipped(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => OrderStatus::Shipped->value,
                'processed_at' => now(),
            ];
        });
    }
}
