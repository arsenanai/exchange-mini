<?php

namespace Tests\Unit;

use App\Models\Asset;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;

test('user has many assets', function () {
    $user = User::factory()
        ->has(Asset::factory()->count(2)->state(new Sequence(
            ['symbol' => 'BTC'],
            ['symbol' => 'ETH'],
        )))
        ->create();

    expect($user->assets)->toHaveCount(2);
    expect($user->assets->first())->toBeInstanceOf(Asset::class);
});

test('user has many orders', function () {
    $user = User::factory()->has(Order::factory()->count(3))->create();

    expect($user->orders)->toHaveCount(3);
    expect($user->orders->first())->toBeInstanceOf(Order::class);
});

test('asset and order belong to a user', function () {
    $user = User::factory()->create();
    $asset = Asset::factory(['user_id' => $user->id])->create();
    $order = Order::factory(['user_id' => $user->id])->create();

    expect($asset->user)->toBeInstanceOf(User::class);
    expect($order->user)->toBeInstanceOf(User::class);
    expect($asset->user->id)->toBe($user->id);
});
