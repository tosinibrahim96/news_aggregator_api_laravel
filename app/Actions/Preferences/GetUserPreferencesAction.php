<?php

declare(strict_types=1);

namespace App\Actions\Preferences;

use App\Contracts\Repositories\PreferenceRepositoryInterface;
use App\Models\User;
use Illuminate\Support\Collection;

class GetUserPreferencesAction
{
    public function __construct(
        private readonly PreferenceRepositoryInterface $repository
    ) {}

    /**
     * Get user preferences
     *
     * @param User $user
     * @return array<string, Collection>
     */
    public function execute(User $user): array
    {
        return $this->repository->getUserPreferences($user);
    }
}
