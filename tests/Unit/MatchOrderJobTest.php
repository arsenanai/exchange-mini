<?php

namespace Tests\Unit;

use App\Jobs\MatchOrderJob;
use App\Models\Order;
use App\Services\MatchingService;

test('it handles a non-existent order id gracefully', function () {
    // Mock the MatchingService
    $matcherMock = $this->mock(MatchingService::class);

    // Expect that tryMatch is never called because the order doesn't exist
    $matcherMock->shouldNotReceive('tryMatch');

    // Create a job with a non-existent order ID
    $job = new MatchOrderJob(99999); // An ID that is unlikely to exist

    // Handle the job
    $job->handle($matcherMock);

    // The main assertion is that no error was thrown and the mock expectation was met.
    // We can use expect(true)->toBeTrue() to make Pest happy.
    expect(true)->toBeTrue();
});

test('it calls the matching service with a valid order', function () {
    // Create a real order
    $order = Order::factory()->create();

    // Mock the MatchingService
    $matcherMock = $this->mock(MatchingService::class);

    // Expect that tryMatch is called once with the correct order object
    $matcherMock->shouldReceive('tryMatch')
        ->once()
        ->withArgs(function ($arg) use ($order) {
            return $arg instanceof Order && $arg->id === $order->id;
        });

    // Create and handle the job
    (new MatchOrderJob($order->id))->handle($matcherMock);
});
