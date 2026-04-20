<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        $subtotal = $this->faker->numberBetween(5000, 40000);
        $shipping = $this->faker->randomElement([0, 3000, 5000]);

        return [
            'user_id' => User::factory(),
            'order_number' => 'CMD-'.now()->format('Y').'-'.strtoupper(Str::random(8)),
            'total' => $subtotal + $shipping,
            'status' => $this->faker->randomElement(['pending', 'processing', 'shipped', 'delivered']),
            'shipping_method' => $this->faker->randomElement(['pickup', 'standard', 'express']),
            'shipping_address' => $this->faker->address(),
            'shipping_cost' => $shipping,
            'tracking_number' => null,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
