<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\InvalidCredentialsException;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    /**
     * @param  array<string, string>  $data
     */
    public function register(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'balance' => config('app.initial_user_balance'),
        ]);
    }

    /**
     * @param  array<string, string>  $data
     * @return array{token: string, user: User}
     *
     * @throws InvalidCredentialsException
     */
    public function login(array $data): array
    {
        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw new InvalidCredentialsException('Invalid credentials');
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return [
            'token' => $token,
            'user' => $user,
        ];
    }

    public function logout(User $user): void
    {
        $user->tokens()->delete();
    }
}
