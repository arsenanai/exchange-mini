<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Asset>
 */
class AssetFactory extends Factory
{
    protected $model = Asset::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'symbol' => $this->faker->randomElement(['BTC', 'ETH']),
            'amount' => $this->faker->randomFloat(8, 1, 10),
            'locked_amount' => '0',
        ];
    }
}
