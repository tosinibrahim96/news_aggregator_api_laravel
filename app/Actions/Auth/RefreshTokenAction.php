<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use Illuminate\Support\Facades\Auth;

class RefreshTokenAction
{
    /**
     * Refresh JWT token
     *
     * @return string
     */
    public function execute(): string
    {
        return Auth::refresh();
    }
}
