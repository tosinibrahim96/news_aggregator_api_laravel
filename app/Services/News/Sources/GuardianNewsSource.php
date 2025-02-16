<?php

declare(strict_types=1);

namespace App\Services\News\Sources;

use App\DTO\ArticleDTO;
use App\Exceptions\NewsSourceException;
use App\Services\News\AbstractNewsSource;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

/**
 * The Guardian news source implementation
 */
class GuardianNewsSource extends AbstractNewsSource
{
    
    /**
     * {@inheritDoc}
     */
    protected function getConfigKey(): string
    {
        return 'guardian';
    }
    
    
    /**
     * {@inheritDoc}
     */
    public function fetchArticlesByCategory(string $category, int $limit = 100): Collection
    {
        $cacheKey = "guardian_articles_{$category}";

        try {
            return Cache::remember(
                $cacheKey,
                now()->addMinutes(15),
                function () use ($category, $limit) {
                    $this->handleRateLimit();

                    $response = $this->client->get('/search', [
                        'api-key' => $this->getApiKey(),
                        'section' => $this->mapCategory($category),
                        'show-fields' => 'all',
                        'page-size' => $limit,
                        'order-by' => 'newest',
                    ])->throw()->json();

                    $this->validateResponse($response);

                    return collect($response['response']['results'])
                        ->map(fn (array $article) => $this->mapToDTO($article, $category));
                }
            );
        } catch (\Exception $e) {
            $this->logError('Failed to fetch articles', [
                'category' => $category,
                'error' => $e->getMessage(),
            ]);
            throw new NewsSourceException("Failed to fetch articles from The Guardian: {$e->getMessage()}");
        }
    }

    /**
     * Map Guardian article to DTO
     *
     * @param array<string, mixed> $article
     * @param string $category
     * @return ArticleDTO
     */
    private function mapToDTO(array $article, string $category): ArticleDTO
    {
        $fields = $article['fields'] ?? [];

        return new ArticleDTO(
            title: $article['webTitle'],
            description: $fields['trailText'] ?? '',
            content: $fields['bodyText'] ?? null,
            url: $article['webUrl'],
            imageUrl: $fields['thumbnail'] ?? null,
            author: $fields['byline'] ?? null,
            publishedAt: Carbon::parse($article['webPublicationDate']),
            externalId: $article['id'],
            category: $category,
            sourceIdentifier: $this->getSourceIdentifier()
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function validateResponse(mixed $response): void
    {
        if (!isset($response['response']['results'])) {
            throw new NewsSourceException('Invalid response format from The Guardian API');
        }
    }
}
