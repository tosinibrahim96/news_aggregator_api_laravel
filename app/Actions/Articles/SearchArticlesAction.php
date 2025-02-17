<?php

declare(strict_types=1);

namespace App\Actions\Articles;

use App\Contracts\Repositories\ArticleRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class SearchArticlesAction
{
    public function __construct(
        private readonly ArticleRepositoryInterface $repository
    ) {}

    /**
     * Search and filter articles
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function execute(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->searchArticles(
            filters: $filters,
            perPage: $perPage
        );
    }
}
