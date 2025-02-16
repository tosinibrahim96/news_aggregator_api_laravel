<?php

declare(strict_types=1);

namespace App\Services\News;

use App\Contracts\Services\NewsSourceInterface;
use App\Exceptions\NewsSourceException;
use App\Models\Source;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Base class for news sources with common functionality
 */
abstract class AbstractNewsSource implements NewsSourceInterface
{
    protected PendingRequest $client;

    /**
     * @param Source $source The source model containing configuration
     */
    public function __construct(
        protected readonly Source $source
    ) {
        if (!$this->isConfigured()) {
            throw new NewsSourceException("Source {$source->name} is not properly configured");
        }

        $this->client = Http::withHeaders($this->getHeaders())
            ->baseUrl($this->getBaseUrl())
            ->timeout(30)
            ->retry(3, 100);
    }

    /**
     * Get the configuration key for this source
     *
     * @return string
     */
    abstract protected function getConfigKey(): string;

    /**
     * Get the source configuration
     *
     * @param string|null $key Specific config key to retrieve
     * @param mixed|null $default Default value if config is not found
     * @return mixed
     */
    protected function getConfig(?string $key = null, mixed $default = null): mixed
    {
        $configPath = 'news.sources.' . $this->getConfigKey();
        
        if ($key) {
            $configPath .= '.' . $key;
        }

        return Config::get($configPath, $default);
    }

    /**
     * Get the base URL for the API
     *
     * @return string
     */
    protected function getBaseUrl(): string
    {
        return $this->getConfig('base_url') ?? $this->source->base_url;
    }

    /**
     * Get the API key
     *
     * @return string
     * @throws NewsSourceException
     */
    protected function getApiKey(): string
    {
        $apiKey = $this->getConfig('api_key');

        if (empty($apiKey)) {
            throw new NewsSourceException("API key not configured for source {$this->getSourceIdentifier()}");
        }

        return $apiKey;
    }

    /**
     * Get headers for the API request
     *
     * @return array<string, string>
     */
    protected function getHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'User-Agent' => config('app.name') . ' News Aggregator',
        ];
    }

    /**
     * Handle rate limiting using cache
     *
     * @throws NewsSourceException
     */
    protected function handleRateLimit(): void
    {
        $key = "rate_limit_{$this->getSourceIdentifier()}";
        $requests = Cache::get($key, 0);

        if ($requests >= $this->getMaxRequestsPerMinute()) {
            throw new NewsSourceException("Rate limit exceeded for {$this->getSourceIdentifier()}");
        }

        Cache::put($key, $requests + 1, now()->addMinutes(1));
    }

    /**
     * Get maximum requests allowed per minute
     *
     * @return int
     */
    protected function getMaxRequestsPerMinute(): int
    {
        return (int) $this->getConfig('max_requests_per_minute', 30);
    }

    /**
     * Map the source category to API-specific category
     *
     * @param string $category
     * @return string
     */
    protected function mapCategory(string $category): string
    {
        return $this->source->getMappedCategory($category);
    }

    /**
     * {@inheritDoc}
     */
    public function isConfigured(): bool
    {
        return !empty($this->getConfig('api_key')) && !empty($this->getBaseUrl()) && $this->source->is_active;
    }

    /**
     * {@inheritDoc}
     */
    public function getSourceIdentifier(): string
    {
        return $this->source->slug;
    }

    /**
     * Validate API response
     *
     * @param mixed $response
     * @throws NewsSourceException
     */
    abstract protected function validateResponse(mixed $response): void;

    /**
     * Log API request error
     *
     * @param string $message
     * @param array<string, mixed> $context
     * @return void
     */
    protected function logError(string $message, array $context = []): void
    {
        Log::error(sprintf('[%s] %s', $this->getSourceIdentifier(), $message), [
            'source' => $this->getSourceIdentifier(),
            'context' => $context,
        ]);
    }
}
