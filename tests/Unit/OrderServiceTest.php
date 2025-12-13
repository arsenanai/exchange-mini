<?php

namespace Tests\Unit;

use App\Enums\OrderStatuses;
use App\Exceptions\InsufficientFundsException;
use App\Exceptions\OrderNotOpenException;
use App\Models\Asset;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\UnauthorizedException;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeEach(function () {
    /** @var TestCase $this */
    $this->orderService = $this->app->make(OrderService::class);
    $this->user = User::factory()->create(['balance' => '10000']);
});

test('it creates a buy order and locks usd', function () {
    /** @var TestCase $this */
    $orderData = ['symbol' => 'BTC', 'side' => 'buy', 'price' => '50000', 'amount' => '0.1'];

    $order = $this->orderService->createOrder($orderData, $this->user);

    expect($order)->toBeInstanceOf(Order::class);
    expect($order->status)->toBe(OrderStatuses::OPEN);
    expect($order->locked_usd)->toBe('5000.00000000');
    $this->user->refresh();
    expect($this->user->balance)->toBe('5000.00000000');
});

test('it throws exception when creating buy order with insufficient funds', function () {
    /** @var TestCase $this */
    $orderData = ['symbol' => 'BTC', 'side' => 'buy', 'price' => '50000', 'amount' => '1'];
    $this->orderService->createOrder($orderData, $this->user);
})->throws(InsufficientFundsException::class, 'Insufficient USD balance');

test('it creates a sell order and locks asset', function () {
    /** @var TestCase $this */
    $asset = Asset::factory()->create(['user_id' => $this->user->id, 'symbol' => 'BTC', 'amount' => '1.0']);
    $orderData = ['symbol' => 'BTC', 'side' => 'sell', 'price' => '50000', 'amount' => '0.5'];

    $order = $this->orderService->createOrder($orderData, $this->user);

    expect($order->status)->toBe(OrderStatuses::OPEN);
    expect($order->locked_asset)->toBe('0.50000000');
    $asset->refresh();
    expect($asset->amount)->toBe('0.50000000');
    expect($asset->locked_amount)->toBe('0.50000000');
});

test('it throws exception when creating sell order with insufficient assets', function () {
    /** @var TestCase $this */
    $orderData = ['symbol' => 'BTC', 'side' => 'sell', 'price' => '50000', 'amount' => '0.5'];
    $this->orderService->createOrder($orderData, $this->user);
})->throws(InsufficientFundsException::class, 'Insufficient asset balance');

test('it cancels a buy order and refunds usd', function () {
    /** @var TestCase $this */
    $order = Order::factory()->create([
        'user_id' => $this->user->id,
        'side' => 'buy',
        'status' => OrderStatuses::OPEN,
        'locked_usd' => '5000',
    ]);
    $initialBalance = $this->user->balance;

    $cancelledOrder = $this->orderService->cancelOrder($order->id, $this->user);

    expect($cancelledOrder->status)->toBe(OrderStatuses::CANCELLED);
    $this->user->refresh();
    expect($this->user->balance)->toBe(bcadd($initialBalance, '5000', 8));
});

test('it cancels a sell order and releases asset', function () {
    /** @var TestCase $this */
    $asset = Asset::factory()->create(['user_id' => $this->user->id, 'symbol' => 'BTC', 'amount' => '0.5', 'locked_amount' => '0.5']);
    $order = Order::factory()->create([
        'user_id' => $this->user->id,
        'symbol' => 'BTC',
        'side' => 'sell',
        'status' => OrderStatuses::OPEN,
        'locked_asset' => '0.5',
    ]);

    $this->orderService->cancelOrder($order->id, $this->user);

    $asset->refresh();
    expect($asset->amount)->toBe('1.00000000');
    expect($asset->locked_amount)->toBe('0.00000000');
});

test('it throws exception when cancelling a non-existent order', function () {
    /** @var TestCase $this */
    $this->orderService->cancelOrder(999, $this->user);
})->throws(ModelNotFoundException::class);

test('it throws exception when cancelling an order that is not open', function () {
    /** @var TestCase $this */
    $order = Order::factory()->create(['user_id' => $this->user->id, 'status' => OrderStatuses::FILLED]);
    $this->orderService->cancelOrder($order->id, $this->user);
})->throws(OrderNotOpenException::class);

test('it throws exception when cancelling another user order', function () {
    /** @var TestCase $this */
    $otherUser = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $otherUser->id, 'status' => OrderStatuses::OPEN]);

    $this->orderService->cancelOrder($order->id, $this->user);
})->throws(UnauthorizedException::class, 'You are not authorized to cancel this order.');

test('it can get all open orders', function () {
    /** @var TestCase $this */
    Order::factory()->count(2)->create(['status' => OrderStatuses::OPEN]);
    Order::factory()->create(['status' => OrderStatuses::FILLED]);

    $orders = $this->orderService->getOpenOrders(null);

    expect($orders)->toHaveCount(2);
    expect($orders->first()->status)->toBe(OrderStatuses::OPEN);
});

test('it can filter open orders by symbol', function () {
    /** @var TestCase $this */
    Order::factory()->create(['status' => OrderStatuses::OPEN, 'symbol' => 'BTC']);
    Order::factory()->create(['status' => OrderStatuses::OPEN, 'symbol' => 'ETH']);

    $orders = $this->orderService->getOpenOrders('BTC');

    expect($orders)->toHaveCount(1);
    expect($orders->first()->symbol)->toBe('BTC');
});

test('it can get all orders for a specific user', function () {
    /** @var TestCase $this */
    Order::factory()->count(3)->create(['user_id' => $this->user->id]);
    Order::factory()->create(); // Another user's order

    $orders = $this->orderService->getUserOrders($this->user);

    expect($orders)->toHaveCount(3);
    expect($orders->first()->user_id)->toBe($this->user->id);
});
