<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\DTO\ArticleDTO;
use Illuminate\Support\Collection;

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
}
