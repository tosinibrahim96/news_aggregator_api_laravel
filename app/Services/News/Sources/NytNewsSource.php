<?php

declare(strict_types=1);

namespace App\Services\News\Sources;

use App\DTO\ArticleDTO;
use App\Exceptions\NewsSourceException;
use App\Services\News\AbstractNewsSource;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * New York Times source implementation
 */
class NytNewsSource extends AbstractNewsSource
{
    /**
     * NYT rate limit configuration
     */
    private const SLEEP_SECONDS = 61;
    private const MAX_RETRIES = 3;

    /**
     * {@inheritDoc}
     */
    protected function getConfigKey(): string
    {
        return 'nyt';
    }

    /**
     * {@inheritDoc}
     */
    public function fetchArticlesByCategory(string $category, int $limit = 100): Collection
    {
        $cacheKey = "nyt_articles_{$category}";

        try {
            return Cache::remember(
                $cacheKey,
                now()->addMinutes(15),
                function () use ($category, $limit) {
                    $this->handleRateLimit();

                    $response = $this->client->get('/news/v3/content/all/' . $this->mapCategory($category) . '.json', [
                        'api-key' => $this->getApiKey(),
                        'limit' => min($limit, 500),
                    ])->throw()->json();

                    $this->validateResponse($response);

                    return collect($response['results'])
                        ->map(fn (array $article) => $this->mapToDTO($article, $category));
                }
            );
        } catch (\Exception $e) {
            $this->logError('Failed to fetch articles', [
                'category' => $category,
                'error' => $e->getMessage(),
            ]);
            throw new NewsSourceException("Failed to fetch articles from NYT: {$e->getMessage()}");
        }
    }


    /**
     * Handle rate limiting with retry mechanism.
     * 
     * NYT only allows 5 requests per minute
     *
     * @throws NewsSourceException
     */
    protected function handleRateLimit(): void
    {
        $attempts = 0;
        $key = "rate_limit_{$this->getSourceIdentifier()}";

        while ($attempts < self::MAX_RETRIES) {
            $requests = Cache::get($key, 0);

            if ($requests < $this->getMaxRequestsPerMinute()) {
                Cache::put($key, $requests + 1, now()->addMinute());
                return;
            }

            $attempts++;
            
            if ($attempts === self::MAX_RETRIES) {
                throw new NewsSourceException(
                    "Rate limit exceeded for {$this->getSourceIdentifier()} after {$attempts} retries"
                );
            }

            Log::info("Rate limit reached for NYT, waiting for next minute", [
                'attempt' => $attempts,
                'max_retries' => self::MAX_RETRIES
            ]);

            sleep(self::SLEEP_SECONDS);
            
            Cache::put($key, 0, now()->addMinute());
        }
    }

    /**
     * Map NYT article to DTO
     *
     * @param array<string, mixed> $article
     * @param string $category
     * @return ArticleDTO
     */
    private function mapToDTO(array $article, string $category): ArticleDTO
    {
        $multimedia = collect($article['multimedia'] ?? [])->first();

        return new ArticleDTO(
            title: $article['title'],
            description: $article['abstract'] ?? '',
            content: $article['lead_paragraph'] ?? null,
            url: $article['url'],
            imageUrl: $multimedia['url'] ?? null,
            author: $article['byline'] ?? null,
            publishedAt: Carbon::parse($article['published_date']),
            externalId: $article['uri'],
            category: $category,
            sourceIdentifier: $this->getSourceIdentifier()
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function validateResponse(mixed $response): void
    {
        if (!isset($response['results']) || $response['status'] !== 'OK') {
            throw new NewsSourceException('Invalid response format from NYT API');
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function getHeaders(): array
    {
        return [
            ...parent::getHeaders(),
            'Accept' => 'application/json',
        ];
    }
}
