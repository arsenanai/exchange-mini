<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use App\Enums\OrderStatuses;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'symbol' => $this->faker->randomElement(['BTC', 'ETH']),
            'side' => $this->faker->randomElement(['buy', 'sell']),
            'price' => $this->faker->randomFloat(8, 2000, 50000),
            'amount' => $this->faker->randomFloat(8, 0.1, 5),
            'status' => OrderStatuses::OPEN,
            'locked_usd' => '0',
            'locked_asset' => '0',
        ];
    }
}
