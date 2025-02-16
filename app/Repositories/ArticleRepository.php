<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\ArticleRepositoryInterface;
use App\DTO\ArticleDTO;
use App\Models\Article;
use App\Models\Category;
use App\Models\Source;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Repository for handling article storage and retrieval
 */
class ArticleRepository implements ArticleRepositoryInterface
{
    /**
     * Track article processing statistics
     */
    private array $stats = [
        'created' => 0,
        'updated' => 0,
        'failed' => 0,
        'total' => 0,
    ];

    public function __construct(
        private readonly Article $model
    ) {}

    /**
     * {@inheritDoc}
     */
    public function storeArticles(Collection $articles, string $sourceIdentifier): array
    {
        $source = $this->getSourceByIdentifier($sourceIdentifier);
        $this->stats['total'] = $articles->count();

        foreach ($articles as $article) {
            try {
                DB::transaction(function () use ($article, $source) {
                    $storedArticle = $this->model->updateOrCreate(
                        $this->getArticleIdentifiers($article, $source),
                        $this->getArticleAttributes($article)
                    );

                    /**
                     * @see \Illuminate\Database\Eloquent\Model::$wasRecentlyCreated
                     */
                    $this->updateStats($storedArticle->wasRecentlyCreated);
                });
            } catch (QueryException $e) {
                $this->handleStorageError($e, $article, $source->slug);
                continue; // Move to next article on error
            } catch (Exception $e) {

                $this->handleStorageError(
                    new QueryException(
                        connectionName: '',
                        bindings: [],
                        sql: 'Unexpected error during article processing',
                        previous: $e
                    ),
                    $article,
                    $source->slug
                );
                continue; // Move to next article on error
            }
        }


        $this->logStatistics($sourceIdentifier);

        return $this->stats;
    }


    /**
     * Get article identifying attributes
     *
     * @param ArticleDTO $articleDto
     * @param Source $source
     * @return array<string, mixed>
     */
    private function getArticleIdentifiers(ArticleDTO $articleDto, Source $source): array
    {
        return [
            'source_id' => $source->id,
            'external_id' => $articleDto->externalId,
        ];
    }

    /**
     * Get article attributes for storage
     *
     * @param ArticleDTO $articleDto
     * @return array<string, mixed>
     */
    private function getArticleAttributes(ArticleDTO $articleDto): array
    {
       return [
            'title' => $articleDto->title,
            'slug' => Str::slug($articleDto->title),
            'description' => $articleDto->description,
            'content' => $articleDto->content,
            'author' => $articleDto->author,
            'url' => $articleDto->url,
            'image_url' => $articleDto->imageUrl,
            'published_at' => $articleDto->publishedAt,
            'category_id' => $this->getCategoryId($articleDto->category),
        ];
    }

    /**
     * Update processing statistics
     *
     * @param bool $wasCreated
     * @return void
     */
    private function updateStats(bool $wasRecentlyCreated): void
    {
        $wasRecentlyCreated ? $this->stats['created']++ : $this->stats['updated']++;
    }

    /**
     * Handle article storage error
     *
     * @param QueryException $e
     * @param ArticleDTO $articleDto
     * @param string $sourceIdentifier
     * @return void
     */
    private function handleStorageError(
        QueryException $e,
        ArticleDTO $articleDto,
        string $sourceIdentifier
    ): void {
        $this->stats['failed']++;

        Log::error('Failed to store article', [
            'source' => $sourceIdentifier,
            'external_id' => $articleDto->externalId,
            'error' => $e->getMessage(),
            'article' => $articleDto,
        ]);
    }

    /**
     * Get source by identifier
     *
     * @param string $sourceIdentifier
     * @return Source
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    private function getSourceByIdentifier(string $sourceIdentifier): Source
    {
        return Source::where('slug', $sourceIdentifier)->firstOrFail();
    }

    /**
     * Log processing statistics
     *
     * @param string $sourceIdentifier
     * @return void
     */
    private function logStatistics(string $sourceIdentifier): void
    {
        Log::info('Article storage statistics', [
            'source' => $sourceIdentifier,
            'stats' => $this->stats,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function existsByExternalId(string $externalId, string $sourceIdentifier): bool
    {
        return $this->model
            ->whereHas('source', fn($query) => $query->where('slug', $sourceIdentifier))
            ->where('external_id', $externalId)
            ->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function getCountBySource(string $sourceIdentifier): int
    {
        return $this->model
            ->whereHas('source', fn($query) => $query->where('slug', $sourceIdentifier))
            ->count();
    }

    /**
     * Get category ID by slug
     *
     * @param string $categorySlug
     * @return int
     * @throws InvalidArgumentException
     */
    private function getCategoryId(string $categorySlug): int
    {
        static $categories = [];

        if (!isset($categories[$categorySlug])) {
            $category = Category::where('slug', $categorySlug)->first();

            if (!$category) {
                throw new InvalidArgumentException("Category not found: {$categorySlug}");
            }

            $categories[$categorySlug] = $category->id;
        }

        return $categories[$categorySlug];
    }
}
