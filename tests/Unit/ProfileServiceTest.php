<?php

namespace Tests\Unit;

use App\Models\Asset;
use App\Models\User;
use App\Services\ProfileService;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

test('it can get a user profile with assets', function () {
    /** @var TestCase $this */
    $profileService = $this->app->make(ProfileService::class);
    $user = User::factory()->has(
        Asset::factory()->count(2)->state(new Sequence(['symbol' => 'BTC'], ['symbol' => 'ETH']))
    )->create();

    // The user model doesn't have assets loaded initially
    expect($user->relationLoaded('assets'))->toBeFalse();

    $profile = $profileService->getProfile($user);

    // The service should load the assets
    expect($profile->relationLoaded('assets'))->toBeTrue();
    expect($profile->assets)->toHaveCount(2);
});
