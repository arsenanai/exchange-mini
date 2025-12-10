<?php

namespace Tests\Feature\Auth;

use App\Models\User;

test('a user can register', function () {
    $response = $this->postJson('/api/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['data' => ['id', 'name', 'email', 'balanceUsd', 'assets']]);

    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
    ]);
});

test('a user can login and get a token', function () {
    $user = User::factory()->create();

    $response = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['token', 'user']);
});

test('a user can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/logout');

    $response->assertStatus(200)->assertJson(['message' => 'Logged out']);
});
