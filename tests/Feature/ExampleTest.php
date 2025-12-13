<?php

use App\Enums\OrderStatuses;
use App\Models\Order;

it('returns a successful response for the open orders endpoint', function () {
    $response = $this->get('/api/orders');

    $response->assertStatus(200);
});

test('it can filter open orders by symbol', function () {
    Order::factory()->create(['symbol' => 'BTC', 'status' => OrderStatuses::OPEN]);
    Order::factory()->create(['symbol' => 'BTC', 'status' => OrderStatuses::OPEN]);
    Order::factory()->create(['symbol' => 'ETH', 'status' => OrderStatuses::OPEN]);

    $response = $this->get('/api/orders?symbol=BTC');

    $response->assertStatus(200)
        ->assertJsonCount(2, 'data');
});
