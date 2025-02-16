<?php

declare(strict_types=1);

namespace App\Services\News\Sources;

use App\DTO\ArticleDTO;
use App\Exceptions\NewsSourceException;
use App\Services\News\AbstractNewsSource;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * NewsAPI source implementation
 */
class NewsApiSource extends AbstractNewsSource
{
    /**
     * {@inheritDoc}
     */
    protected function getConfigKey(): string
    {
        return 'newsapi';
    }

    /**
     * {@inheritDoc}
     */
    protected function getHeaders(): array
    {
        return [
            ...parent::getHeaders(),
            'X-Api-Key' => $this->getApiKey(),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function fetchArticlesByCategory(string $category, int $limit = 100): Collection
    {
        $cacheKey = "newsapi_articles_{$category}";

        try {
            return Cache::remember(
                $cacheKey,
                now()->addMinutes(15),
                function () use ($category, $limit) {
                    $this->handleRateLimit();

                    $response = $this->client->get('/top-headlines', [
                        'category' => $this->mapCategory($category),
                        'pageSize' => min($limit, 100),
                        'language' => 'en',
                    ])->throw()->json();

                    $this->validateResponse($response);

                    return collect($response['articles'])
                        ->map(fn (array $article) => $this->mapToDTO($article, $category));
                }
            );
        } catch (\Exception $e) {
            $this->logError('Failed to fetch articles', [
                'category' => $category,
                'error' => $e->getMessage(),
            ]);
            throw new NewsSourceException("Failed to fetch articles from NewsAPI: {$e->getMessage()}");
        }
    }

    /**
     * Map NewsAPI article to DTO
     *
     * @param array<string, mixed> $article
     * @param string $category
     * @return ArticleDTO
     */
    private function mapToDTO(array $article, string $category): ArticleDTO
    {
        return new ArticleDTO(
            title: $article['title'],
            description: $article['description'] ?? '',
            content: $article['content'] ?? null,
            url: $article['url'],
            imageUrl: $article['urlToImage'] ?? null,
            author: $article['author'] ?? null,
            publishedAt: Carbon::parse($article['publishedAt']),
            externalId: md5($article['url']), // NewsAPI doesn't provide unique IDs
            category: $category,
            sourceIdentifier: $this->getSourceIdentifier()
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function validateResponse(mixed $response): void
    {
        if (!isset($response['articles']) || $response['status'] !== 'ok') {
            throw new NewsSourceException('Invalid response format from NewsAPI');
        }
    }
}
