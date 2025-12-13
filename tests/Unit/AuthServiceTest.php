<?php

namespace Tests\Unit;

use App\Exceptions\InvalidCredentialsException;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeEach(function () {
    /** @var TestCase $this */
    $this->authService = $this->app->make(AuthService::class);
});

test('it can register a new user', function () {
    /** @var TestCase $this */
    $userData = [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
    ];

    $user = $this->authService->register($userData);

    expect($user)->toBeInstanceOf(User::class);
    $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    expect($user->balance)->toBe('10000.00000000');
});

test('it can log in a user with correct credentials', function () {
    /** @var TestCase $this */
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $result = $this->authService->login(['email' => $user->email, 'password' => 'password']);

    expect($result['user']->id)->toBe($user->id);
    expect($result['token'])->toBeString();
});

test('it throws exception for invalid login credentials', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();

    $this->authService->login(['email' => $user->email, 'password' => 'wrong-password']);
})->throws(InvalidCredentialsException::class);

test('it can log out a user', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $user->createToken('test-token')->plainTextToken;

    // Pre-assertion: ensure token exists
    $this->assertDatabaseHas('personal_access_tokens', ['tokenable_id' => $user->id]);

    $this->authService->logout($user);

    $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $user->id]);
});
