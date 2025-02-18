<?php

declare(strict_types=1);

namespace App\Actions\Articles;

use App\Contracts\Repositories\ArticleRepositoryInterface;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class SearchArticlesAction
{
    public function __construct(
        private readonly ArticleRepositoryInterface $repository
    ) {}

    /**
     * Search and filter articles with optional user preferences
     *
     * @param array<string, mixed> $filters
     * @param User|null $user
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function execute(array $filters, ?User $user = null, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->searchArticles(
            filters: $filters,
            user: $user,
            perPage: $perPage
        );
    }
}
