<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\AuthRepositoryInterface;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthRepository implements AuthRepositoryInterface
{    
    /**
     * __construct
     *
     * @return void
     */
    public function __construct(
        private readonly User $model
    ) {}

    /**
     * {@inheritdoc}
     */
    public function createUser(array $data): User
    {
        return $this->model->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function findByEmail(string $email): ?User
    {
        return $this->model->where('email', $email)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function deleteUserTokens(User $user): void
    {
        $user->tokens()->delete();
    }
}
