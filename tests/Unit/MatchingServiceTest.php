<?php

namespace Tests\Unit;

use App\Enums\MatchResults;
use App\Enums\OrderStatuses;
use App\Events\OrderMatched;
use App\Models\Asset;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use App\Services\MatchingService;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->orderService = $this->app->make(OrderService::class);
    $this->buyer = User::factory()->create(['balance' => '50750.00']); // 50000 for order + 750 for max fee

    // Seller setup
    $this->seller = User::factory()->create(['balance' => '10000.00']);
    Asset::factory()->create([
        'user_id' => $this->seller->id,
        'symbol' => 'BTC',
        'amount' => '2.0',
        'locked_amount' => '0.0', // Ensure initial locked amount is 0
    ]);

    $this->matchingService = $this->app->make(MatchingService::class);
});

test('it can match a new buy order with an existing sell order', function () {
    Event::fake();

    // Arrange: Use the OrderService to create the initial state
    $sellOrder = $this->orderService->createOrder([
        'symbol' => 'BTC',
        'side' => 'sell',
        'price' => '49000.00',
        'amount' => '1.0',
    ], $this->seller);

    $buyOrder = $this->orderService->createOrder([
        'symbol' => 'BTC',
        'side' => 'buy',
        'price' => '50000.00', // Higher price, should match
        'amount' => '1.0',
    ], $this->buyer);

    // Act
    $result = $this->matchingService->tryMatch($buyOrder);

    // Assert
    expect($result)->toBeArray()->toHaveKeys(['price', 'amount', 'buyer_fee_usd', 'seller_fee_asset']);
    $buyOrder->refresh();
    $sellOrder->refresh();
    expect($buyOrder->status)->toBe(OrderStatuses::FILLED);
    expect($sellOrder->status)->toBe(OrderStatuses::FILLED);

    $this->buyer->refresh();
    $this->seller->refresh();
    $buyerAsset = $this->buyer->assets()->where('symbol', 'BTC')->first();
    $sellerAsset = $this->seller->assets()->where('symbol', 'BTC')->first();

    // Buyer's final balance: 50750 (initial) - 50000 (locked) + [50000 (locked) - (49000 (cost) + 735 (fee))] = 1015
    expect($this->buyer->balance)->toEqual('1015.00000000');

    // Buyer gets 1 - 0.015 = 0.985 BTC
    expect($buyerAsset->amount)->toEqual('0.98500000');
    // Seller gets 49000 USD
    expect($this->seller->refresh()->balance)->toEqual(bcadd('10000.00', '49000.00', 8)); // 59000.00
    // Seller sold 1 BTC from their original 2. They have 1.0 left available, and 0.0 locked. The `amount` field represents available, not total.
    expect($sellerAsset->refresh()->amount)->toEqual('1.00000000');
    expect($sellerAsset->locked_amount)->toEqual('0.00000000');

    $this->assertDatabaseHas('trades', [
        'buy_order_id' => $buyOrder->id,
        'sell_order_id' => $sellOrder->id,
        'price' => '49000.00000000',
    ]);

    Event::assertDispatched(OrderMatched::class, function ($event) use ($buyOrder) {
        return $event->buyOrder->id === $buyOrder->id;
    });
});

test('it can match a new sell order with an existing buy order', function () {
    Event::fake();

    // Arrange: Use the OrderService to create the initial state
    $buyOrder = $this->orderService->createOrder([
        'symbol' => 'BTC',
        'side' => 'buy',
        'price' => '50000.00',
        'amount' => '1.0',
    ], $this->buyer);

    $sellOrder = $this->orderService->createOrder([
        'symbol' => 'BTC',
        'side' => 'sell',
        'price' => '49000.00', // Lower price, should match
        'amount' => '1.0',
    ], $this->seller);

    // Act
    $result = $this->matchingService->tryMatch($sellOrder);

    // Assert
    expect($result)->toBeArray()->toHaveKeys(['price', 'amount', 'buyer_fee_usd', 'seller_fee_asset']);
    $buyOrder->refresh();
    $sellOrder->refresh();
    expect($buyOrder->status)->toBe(OrderStatuses::FILLED);
    expect($sellOrder->status)->toBe(OrderStatuses::FILLED);

    $buyerAsset = $this->buyer->assets()->where('symbol', 'BTC')->first();
    $sellerAsset = $this->seller->assets()->where('symbol', 'BTC')->first();

    expect($this->buyer->refresh()->balance)->toEqual('1015.00000000');

    // Buyer gets 1 - (1 * 0.015) = 0.985 BTC
    expect($buyerAsset->amount)->toEqual('0.98500000');
    // Seller gets 49900 USD added to their initial 10k
    expect($this->seller->refresh()->balance)->toEqual(bcadd('10000.00', '49000.00', 8));

    Event::assertDispatched(OrderMatched::class);
});

test('it does not match a new sell order if no counter buy order exists', function () {
    $sellOrder = $this->orderService->createOrder([
        'symbol' => 'BTC',
        'side' => 'sell',
        'price' => '49000.00',
        'amount' => '1.0',
    ], $this->seller);

    $result = $this->matchingService->tryMatch($sellOrder);

    expect($result)->toBe(MatchResults::NO_COUNTER_ORDER);
    $sellOrder->refresh();
    expect($sellOrder->status)->toBe(OrderStatuses::OPEN);
});

test('it does not match if no counter order exists', function () {
    $buyOrder = $this->orderService->createOrder([
        'symbol' => 'BTC',
        'side' => 'buy',
        'price' => '50000.00',
        'amount' => '1.0',
    ], $this->buyer);

    $result = $this->matchingService->tryMatch($buyOrder);

    expect($result)->toBe(MatchResults::NO_COUNTER_ORDER);
    $buyOrder->refresh();
    expect($buyOrder->status)->toBe(OrderStatuses::OPEN);
});

test('it does not match if amounts are not equal', function () {
    $this->orderService->createOrder([
        'symbol' => 'BTC',
        'side' => 'sell',
        'price' => '49900.00',
        'amount' => '1.0',
    ], $this->seller);

    $buyOrder = $this->orderService->createOrder([
        'symbol' => 'BTC',
        'side' => 'buy',
        'price' => '50000.00',
        'amount' => '0.5', // Different amount
    ], $this->buyer);

    $result = $this->matchingService->tryMatch($buyOrder);

    expect($result)->toBe(MatchResults::AMOUNTS_NOT_EQUAL);
    $buyOrder->refresh();
    expect($buyOrder->status)->toBe(OrderStatuses::OPEN);
});

test('it handles data inconsistency with seller asset gracefully', function () {
    // Arrange: Create a valid sell order first using the service.
    $sellOrder = $this->orderService->createOrder([
        'symbol' => 'BTC',
        'side' => 'sell',
        'price' => '49000.00',
        'amount' => '1.0',
    ], $this->seller);

    // Now, manually introduce the data inconsistency we want to test.
    $sellerAsset = $this->seller->assets()->where('symbol', 'BTC')->first();
    $sellerAsset->locked_amount = '0.5'; // In reality, only 0.5 is locked
    $sellerAsset->save();

    // Create a valid counter-order.
    $buyOrder = $this->orderService->createOrder(['symbol' => 'BTC', 'side' => 'buy', 'price' => '50000.00', 'amount' => '1.0'], $this->buyer);

    $result = $this->matchingService->tryMatch($buyOrder);

    expect($result)->toBe(MatchResults::INSUFFICIENT_SELLER_ASSET_LOCKED);
    $buyOrder->refresh();
    $sellOrder->refresh();
    expect($buyOrder->status)->toBe(OrderStatuses::OPEN);
    expect($sellOrder->status)->toBe(OrderStatuses::OPEN);
});

test('it does not match an order that is not open', function () {
    $buyOrder = Order::factory()->create([
        'user_id' => $this->buyer->id,
        'symbol' => 'BTC',
        'side' => 'buy',
        'price' => '50000.00',
        'amount' => '1.0',
        'status' => OrderStatuses::FILLED, // Already filled
    ]);

    $result = $this->matchingService->tryMatch($buyOrder);

    expect($result)->toBe(MatchResults::ORDER_NOT_OPEN_OR_INVALID);
});

test('it does not match if buyer cannot afford fee despite price improvement', function () {
    $sellOrder = $this->orderService->createOrder([
        'symbol' => 'BTC',
        'side' => 'sell',
        'price' => '49999.00',
        'amount' => '1.0',
    ], $this->seller);

    $buyOrder = $this->orderService->createOrder([
        'symbol' => 'BTC',
        'side' => 'buy',
        'price' => '50000.00',
        'amount' => '1.0',
    ], $this->buyer);

    $result = $this->matchingService->tryMatch($buyOrder);

    expect($result)->toBe(MatchResults::INSUFFICIENT_BUYER_FUNDS);
    expect($buyOrder->refresh()->status)->toBe(OrderStatuses::OPEN);
    expect($sellOrder->refresh()->status)->toBe(OrderStatuses::OPEN);
});

test('it handles a missing buyer record mid-transaction', function () {
    $sellOrder = $this->orderService->createOrder([
        'symbol' => 'BTC',
        'side' => 'sell',
        'price' => '49000.00',
        'amount' => '1.0',
    ], $this->seller);

    // Create a mock of the User model.
    $userMock = $this->mock(User::class);

    // Expect `where` to be called with the buyer's ID and chain the next calls.
    $userMock->shouldReceive('where')->with('id', $this->buyer->id)->andReturn($userMock);
    // When `lockForUpdate->first` is called, return null to simulate the user not being found.
    $userMock->shouldReceive('lockForUpdate->first')->andReturn(null);
    // For the seller lookup, allow the call to pass through to the real model.
    $userMock->shouldReceive('where')->with('id', $this->seller->id)->passthru();

    $buyOrder = $this->orderService->createOrder(['symbol' => 'BTC', 'side' => 'buy', 'price' => '50000.00', 'amount' => '1.0'], $this->buyer);

    // Call the service, injecting our mock.
    expect($this->matchingService->tryMatch($buyOrder, $userMock))->toBe(MatchResults::BUYER_NOT_FOUND);
});
