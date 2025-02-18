<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\User;
use Illuminate\Support\Collection;

interface PreferenceRepositoryInterface
{
    /**
     * Get user preferences
     *
     * @param User $user
     * @return array<string, Collection>
     */
    public function getUserPreferences(User $user): array;

    /**
     * Update user preferences
     *
     * @param User $user
     * @param array<string, array<int, string>> $preferences
     * @return array<string, Collection>
     */
    public function updatePreferences(User $user, array $preferences): array;
}
