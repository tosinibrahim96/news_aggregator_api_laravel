<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Contracts\Repositories\AuthRepositoryInterface;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class RegisterAction
{
    public function __construct(
        private readonly AuthRepositoryInterface $repository
    ) {}

    /**
     * Handle user registration
     *
     * @param array<string, string> $data
     * @return array{user: User, token: string}
     */
    public function execute(array $data): array
    {
        $user = $this->repository->createUser($data);
        $token = Auth::login($user);

        return [
            'user' => $user,
            'token' => $token,
        ];
    }
}
