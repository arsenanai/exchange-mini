<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    public \App\Models\User $user;

    public \App\Models\User $buyer;

    public \App\Models\User $seller;

    public \App\Services\MatchingService $matchingService;

    public \App\Services\OrderService $orderService;
}
