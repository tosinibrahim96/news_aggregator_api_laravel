<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\ArticleRepositoryInterface;
use App\DTO\ArticleDTO;
use App\Models\Article;
use App\Models\Category;
use App\Models\Source;
use App\Models\User;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

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

    /**
     * {@inheritDoc}
     */
    public function searchArticles(array $filters, ?User $user = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->query()
            ->with(['source', 'category']);

        $this->applyFilters($query, $filters);


        // Apply preference-based sorting if user is provided
        if ($user) {
            $this->applyPreferenceSorting($query, $user);
        } else {
            $this->applySorting($query, $filters['sort_by'] ?? 'published_at');
        }

        return $query->paginate($perPage);
    }


    /**
     * Apply filters to the query
     *
     * @param Builder $query
     * @param array<string, mixed> $filters
     * @return void
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['keyword'])) {
            $keyword = $filters['keyword'];
            $query->where(function (Builder $query) use ($keyword) {
                $query->where('title', 'like', "%{$keyword}%")
                    ->orWhere('description', 'like', "%{$keyword}%")
                    ->orWhere('content', 'like', "%{$keyword}%");
            });
        }

        if (!empty($filters['source'])) {
            $query->whereHas('source', function (Builder $query) use ($filters) {
                $query->where('slug', $filters['source']);
            });
        }

        if (!empty($filters['category'])) {
            $query->whereHas('category', function (Builder $query) use ($filters) {
                $query->where('slug', $filters['category']);
            });
        }

        if (!empty($filters['author'])) {
            $query->where('author', 'like', "%{$filters['author']}%");
        }

        if (!empty($filters['date_from'])) {
            $query->where('published_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('published_at', '<=', $filters['date_to']);
        }
    }


    /**
     * Apply sorting to the query
     *
     * @param Builder $query
     * @param string $sortBy
     * @return void
     */
    private function applySorting(Builder $query, string $sortBy): void
    {
        $direction = 'asc';

        if (str_starts_with($sortBy, '-')) {
            $sortBy = substr($sortBy, 1);
            $direction = 'desc';
        }

        $allowedSortFields = ['published_at', 'title'];

        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $direction);
        } else {
            $query->orderBy('published_at', 'desc');
        }
    }



    /**
     * Apply preference-based sorting to the query
     *
     * @param Builder $query
     * @param User $user
     * @return void
     */
    private function applyPreferenceSorting(Builder $query, User $user): void
    {
        $preferences = $user->preferences()
            ->select('source_id', 'category_id', 'author_name')
            ->get();

        $sources = $preferences->pluck('source_id')->filter()->toArray();
        $categories = $preferences->pluck('category_id')->filter()->toArray();
        $authors = $preferences->pluck('author_name')->filter()->toArray();

        $sourcesPlaceholder   = $this->buildPlaceholders($sources);
        $categoriesPlaceholder= $this->buildPlaceholders($categories);
        $authorsPlaceholder   = $this->buildPlaceholders($authors);
        
        $sql = "
        CASE
            WHEN source_id IN ($sourcesPlaceholder)
                 AND category_id IN ($categoriesPlaceholder)
                 AND author IN ($authorsPlaceholder)
            THEN 1

            WHEN (
                    source_id IN ($sourcesPlaceholder)
                    AND category_id IN ($categoriesPlaceholder)
                 )
                 OR (
                    source_id IN ($sourcesPlaceholder)
                    AND author IN ($authorsPlaceholder)
                 )
                 OR (
                    category_id IN ($categoriesPlaceholder)
                    AND author IN ($authorsPlaceholder)
                 )
            THEN 2

            WHEN source_id IN ($sourcesPlaceholder)
                 OR category_id IN ($categoriesPlaceholder)
                 OR author IN ($authorsPlaceholder)
            THEN 3

            ELSE 4
        END
        ";

        

        $params = [
            ...$sources, ...$categories, ...$authors,
            ...$sources, ...$categories,
            ...$sources, ...$authors,
            ...$categories, ...$authors,
            ...$sources, ...$categories, ...$authors,
        ];

        $query->orderByRaw("$sql, published_at DESC", $params);
    }



    /**
     * Build a comma-separated list of "?" placeholders matching the count of $items.
     * If the array is empty, return "NULL" so "IN (NULL)" won't match anything.
     *
     * @param array $items
     * @return string
     */
    private function buildPlaceholders(array $items): string
    {
        if (empty($items)) {
            return 'NULL';
        }

        // e.g. if $items has 3 elements, return "?,?,?"
        return implode(',', array_fill(0, count($items), '?'));
    }
}
