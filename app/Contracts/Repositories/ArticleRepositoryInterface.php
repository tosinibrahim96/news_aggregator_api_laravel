<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\DTO\ArticleDTO;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface for article repository implementations
 */
interface ArticleRepositoryInterface
{
    /**
     * Store multiple articles, handling duplicates and updates
     *
     * @param Collection<int, ArticleDTO> $articles
     * @param string $sourceIdentifier
     * @return array{
     *     created: int,
     *     updated: int,
     *     failed: int,
     *     total: int
     * }
     */
    public function storeArticles(Collection $articles, string $sourceIdentifier): array;

    /**
     * Check if an article exists by external ID and source
     *
     * @param string $externalId
     * @param string $sourceIdentifier
     * @return bool
     */
    public function existsByExternalId(string $externalId, string $sourceIdentifier): bool;

    /**
     * Get the count of articles by source
     *
     * @param string $sourceIdentifier
     * @return int
     */
    public function getCountBySource(string $sourceIdentifier): int;

    /**
     * Search articles with preference-based sorting
     *
     * @param array<string, mixed> $filters
     * @param User|null $user
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function searchArticles(array $filters, ?User $user = null, int $perPage = 15): LengthAwarePaginator;
}
