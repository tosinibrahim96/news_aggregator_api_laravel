<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\User;

interface AuthRepositoryInterface
{
    /**
     * Create a new user
     *
     * @param array<string, string> $data
     * @return User
     */
    public function createUser(array $data): User;

    /**
     * Find user by email
     *
     * @param string $email
     * @return User|null
     */
    public function findByEmail(string $email): ?User;

    /**
     * Delete user tokens
     *
     * @param User $user
     * @return void
     */
    public function deleteUserTokens(User $user): void;
}
