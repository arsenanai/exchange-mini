<?php

namespace Tests\Feature\Order;

use App\Enums\OrderStatuses;
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
        ->assertJsonPath('data.symbol', 'BTC')
        ->assertJsonPath('data.side', 'buy');

    $this->assertDatabaseHas('orders', [
        'user_id' => $this->user->id,
        'status' => OrderStatuses::OPEN,
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
        'status' => OrderStatuses::OPEN,
    ]);

    $asset = Asset::where('user_id', $this->user->id)->where('symbol', 'BTC')->firstOrFail();
    expect($asset->amount)->toBe('0.50000000');
    expect($asset->locked_amount)->toBe('0.50000000');
});

test('user cannot create a sell order with insufficient assets', function () {
    // User has no BTC asset
    $orderData = [
        'symbol' => 'BTC',
        'side' => 'sell',
        'price' => '50000',
        'amount' => '0.5',
    ];

    $response = $this->postJson('/api/orders', $orderData);

    $response->assertStatus(422)
        ->assertJson(['message' => 'Insufficient asset balance']);

    $this->assertDatabaseMissing('orders', ['user_id' => $this->user->id]);
});

test('user can cancel their own open order', function () {
    $order = Order::factory()->create([
        'user_id' => $this->user->id,
        'side' => 'buy',
        'status' => OrderStatuses::OPEN,
        'locked_usd' => '5000.00000000',
    ]);

    $initialBalance = $this->user->balance;

    $response = $this->postJson("/api/orders/{$order->id}/cancel");

    $response->assertOk()
        ->assertJsonPath('data.status', OrderStatuses::CANCELLED->value);

    $this->user->refresh();
    $expectedBalance = bcadd($initialBalance, '5000.00000000', 8);
    expect($this->user->balance)->toBe($expectedBalance);
});

test('user can cancel their own open sell order', function () {
    $asset = Asset::factory()->create([
        'user_id' => $this->user->id,
        'symbol' => 'BTC',
        'amount' => '0.5',
        'locked_amount' => '0.5',
    ]);

    $order = Order::factory()->create([
        'user_id' => $this->user->id,
        'side' => 'sell',
        'symbol' => 'BTC',
        'status' => OrderStatuses::OPEN,
        'locked_asset' => '0.5',
    ]);

    $response = $this->postJson("/api/orders/{$order->id}/cancel");

    $response->assertOk()
        ->assertJsonPath('data.status', OrderStatuses::CANCELLED->value);

    $asset->refresh();
    expect($asset->amount)->toBe('1.00000000');
    expect($asset->locked_amount)->toBe('0.00000000');
});

test('user cannot cancel a filled order', function () {
    $order = Order::factory()->create([
        'user_id' => $this->user->id,
        'status' => OrderStatuses::FILLED,
    ]);

    $response = $this->postJson("/api/orders/{$order->id}/cancel");

    $response->assertStatus(422)
        ->assertJson(['message' => 'Order not open']);

    expect($order->refresh()->status)->toBe(OrderStatuses::FILLED);
});

test('user cannot cancel another user order', function () {
    $otherUser = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $otherUser->id,
        'status' => OrderStatuses::OPEN,
    ]);

    $response = $this->postJson("/api/orders/{$order->id}/cancel");

    $response->assertForbidden();
    $response->assertJson(['message' => 'This action is unauthorized.']);
});

test('user can list all their orders', function () {
    Order::factory()->count(3)->create(['user_id' => $this->user->id]);
    // Create an order for another user that should not be returned
    Order::factory()->create();

    $response = $this->getJson('/api/orders/all');

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.userId', $this->user->id);
});
