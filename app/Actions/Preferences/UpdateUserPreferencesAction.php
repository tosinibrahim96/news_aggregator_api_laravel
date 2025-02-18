<?php

declare(strict_types=1);

namespace App\Actions\Preferences;

use App\Contracts\Repositories\PreferenceRepositoryInterface;
use App\Models\User;
use Illuminate\Support\Collection;

class UpdateUserPreferencesAction
{
    public function __construct(
        private readonly PreferenceRepositoryInterface $repository
    ) {}

    /**
     * Update user preferences
     *
     * @param User $user
     * @param array<string, array<int, string>> $preferences
     * @return array<string, Collection>
     */
    public function execute(User $user, array $preferences): array
    {
        return $this->repository->updatePreferences($user, $preferences);
    }
}