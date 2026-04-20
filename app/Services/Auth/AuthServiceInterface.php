<?php

namespace App\Services\Auth;

use App\Models\User;

interface AuthServiceInterface
{
    public function register(array $data): array;

    public function login(array $credentials): array;

    public function logout(User $user): array;

    public function me(User $user): User;

    public function refresh(string $refreshToken): array;
}
