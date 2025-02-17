<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class LoginAction
{
   /**
     * Handle user login
     *
     * @param array<string, string> $credentials
     * @return array{user: User, token: string}|null
     */
    public function execute(array $credentials): ?array
    {
        if (!$token = Auth::attempt($credentials)) {
            return null;
        }

        return [
            'user' => Auth::user(),
            'token' => $token,
        ];
    }
}

