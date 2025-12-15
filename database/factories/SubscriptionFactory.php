<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subscription>
 */
class SubscriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(), 
            'price_in_cents' => fake()->numberBetween(1000, 9990), 
            'billing_cycle' => fake()->randomElement(['monthly', 'yearly', 'weekly']), 
            'next_billing_date' => fake()->dateTimeBetween('now', '+1 year'), 
            'status' => fake()->randomElement(['active', 'inactive']),
            'notes' => fake()->sentence()
        ];
    }
}
