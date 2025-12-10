<?php

namespace Tests\Feature\Profile;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;

test('authenticated user can fetch their profile', function () {
    $user = User::factory()->has(
        Asset::factory()->count(2)->state(new Sequence(['symbol' => 'BTC'], ['symbol' => 'ETH']))
    )->create();

    $response = $this->actingAs($user)->getJson('/api/profile');

    $response->assertStatus(200)
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.balanceUsd', $user->balance)
        ->assertJsonCount(2, 'data.assets');
});

test('unauthenticated user cannot fetch a profile', function () {
    $response = $this->getJson('/api/profile');

    $response->assertStatus(401);
});
