<?php

namespace Tests\Feature\Order;

use App\Models\Asset;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->user = User::factory()->create([
        'balance' => '10000',
    ]);
    $this->actingAs($this->user);
    Queue::fake();
});

test('user can create a buy order with sufficient funds', function () {
    $orderData = [
        'symbol' => 'BTC',
        'side' => 'buy',
        'price' => '50000',
        'amount' => '0.1',
    ];

    $response = $this->postJson('/api/orders', $orderData);

    $response->assertStatus(200)
        ->assertJsonPath('order.symbol', 'BTC')
        ->assertJsonPath('order.side', 'buy');

    $this->assertDatabaseHas('orders', [
        'user_id' => $this->user->id,
        'status' => Order::STATUS_OPEN,
    ]);

    $this->user->refresh();
    expect($this->user->balance)->toBe('5000.00000000');
});

test('user cannot create a buy order with insufficient funds', function () {
    $orderData = [
        'symbol' => 'BTC',
        'side' => 'buy',
        'price' => '50000',
        'amount' => '1', // Requires 50000 USD, user has 10000
    ];

    $response = $this->postJson('/api/orders', $orderData);

    $response->assertStatus(422)
        ->assertJson(['message' => 'Insufficient USD balance']);
});

test('user can create a sell order with sufficient assets', function () {
    Asset::factory()->create([
        'user_id' => $this->user->id,
        'symbol' => 'BTC',
        'amount' => '1.0',
    ]);

    $orderData = [
        'symbol' => 'BTC',
        'side' => 'sell',
        'price' => '50000',
        'amount' => '0.5',
    ];

    $response = $this->postJson('/api/orders', $orderData);

    $response->assertStatus(200);

    $this->assertDatabaseHas('orders', [
        'user_id' => $this->user->id,
        'side' => 'sell',
        'status' => Order::STATUS_OPEN,
    ]);

    $asset = Asset::where('user_id', $this->user->id)->where('symbol', 'BTC')->firstOrFail();
    expect($asset->amount)->toBe('0.50000000');
    expect($asset->locked_amount)->toBe('0.50000000');
});

test('user can cancel their own open order', function () {
    $order = Order::factory()->create([
        'user_id' => $this->user->id,
        'side' => 'buy',
        'status' => Order::STATUS_OPEN,
        'locked_usd' => '5000.00000000',
    ]);

    $initialBalance = $this->user->balance;

    $response = $this->postJson("/api/orders/{$order->id}/cancel");

    $response->assertStatus(200)
        ->assertJsonPath('data.status', Order::STATUS_CANCELLED);

    $this->user->refresh();
    $expectedBalance = bcadd($initialBalance, '5000.00000000', 8);
    expect($this->user->balance)->toBe($expectedBalance);
});

test('user cannot cancel another user order', function () {
    $otherUser = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $otherUser->id,
        'status' => Order::STATUS_OPEN,
    ]);

    $response = $this->postJson("/api/orders/{$order->id}/cancel");

    $response->assertStatus(404);
});
