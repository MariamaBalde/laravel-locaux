<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vendeur>
 */
class VendeurFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state([
                'role' => 'vendeur',
                'country' => 'SN',
                'statut' => 'actif',
            ]),
            'shop_name' => $this->faker->company(),
            'description' => $this->faker->optional()->sentence(),
            'verified' => true,
            'rating' => $this->faker->randomFloat(1, 3, 5),
            'total_sales' => $this->faker->numberBetween(0, 500),
        ];
    }
}
