<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;

class ProfileService
{
    public function getProfile(User $user): User
    {
        return $user->load('assets');
    }
}
