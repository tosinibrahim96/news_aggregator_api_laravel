<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use Illuminate\Support\Facades\Auth;

class LogoutAction
{
    /**
     * Handle user logout
     *
     * @return void
     */
    public function execute(): void
    {
        Auth::logout();
    }
}
