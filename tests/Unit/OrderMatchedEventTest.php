<?php

namespace Tests\Unit;

use App\Events\OrderMatched;
use App\Http\Resources\OrderResource;
use App\Models\Order;

test('it has correct broadcast data and channels', function () {
    $buyOrder = Order::factory()->make(['id' => 1, 'user_id' => 10]);
    $sellOrder = Order::factory()->make(['id' => 2, 'user_id' => 20]);

    $event = new OrderMatched(
        buyOrder: $buyOrder,
        sellOrder: $sellOrder,
        symbol: 'BTC',
        price: '50000',
        amount: '1'
    );

    // 1. Test broadcastOn()
    $channels = $event->broadcastOn();
    expect($channels)->toHaveCount(2);
    expect($channels[0]->name)->toBe('private-user.10');
    expect($channels[1]->name)->toBe('private-user.20');

    // 2. Test broadcastAs()
    expect($event->broadcastAs())->toBe('OrderMatched');

    // 3. Test broadcastWith()
    $payload = $event->broadcastWith();
    expect($payload['symbol'])->toBe('BTC');
    expect($payload['buyOrder'])->toBeInstanceOf(OrderResource::class);
    expect($payload['sellOrder'])->toBeInstanceOf(OrderResource::class);
});
