<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use App\DTOs\ArticleDTO;
use Illuminate\Support\Collection;

/**
 * Contract for news source implementations
 */
interface NewsSourceInterface
{
    /**
     * Fetch articles for a specific category
     *
     * @param string $category The category to fetch articles for
     * @param int $limit Maximum number of articles to fetch
     * @return Collection<int, ArticleDTO>
     * @throws \App\Exceptions\NewsSourceException
     */
    public function fetchArticlesByCategory(string $category, int $limit = 100): Collection;

    /**
     * Get the source identifier
     *
     * @return string
     */
    public function getSourceIdentifier(): string;

    /**
     * Check if the source is properly configured
     *
     * @return bool
     */
    public function isConfigured(): bool;
}
