<?php

namespace Tests\Feature\Articles;

use Tests\TestCase;
use App\Jobs\News\FetchArticlesJob;
use App\DTO\ArticleDTO;
use App\Models\Category;
use App\Models\Source;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Exceptions\NewsSourceException;
use App\Contracts\Repositories\ArticleRepositoryInterface;

class FetchArticlesJobTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Guardian as the source, ensures articles are stored.
     */
    public function test_fetch_articles_job_stores_articles_for_guardian(): void
    {
        $guardianSource = Source::factory()->create([
            'slug' => 'guardian',
        ]);

        $category = Category::factory()->create([
            'slug' => 'politics',
        ]);

        $fakeGuardian = $this->createFakeSource('guardian', [
            [
                'title'       => 'Guardian Title 1',
                'description' => 'Guardian Desc 1',
                'content'     => 'Guardian Content 1',
                'url'         => 'https://example.com/guardian1',
                'imageUrl'    => 'https://example.com/guardian1.jpg',
                'author'      => 'Guardian Author 1',
                'publishedAt' => now()->subDay(),
                'externalId'  => 'guardian-article-1',
                'category'    => $category->slug
            ],
            [
                'title'       => 'Guardian Title 2',
                'description' => 'Guardian Desc 2',
                'content'     => 'Guardian Content 2',
                'url'         => 'https://example.com/guardian2',
                'imageUrl'    => 'https://example.com/guardian2.jpg',
                'author'      => 'Guardian Author 2',
                'publishedAt' => now(),
                'externalId'  => 'guardian-article-2',
                'category'    => $category->slug
            ],
        ]);

        app()->bind('news.source.guardian', fn() => $fakeGuardian);

        Log::shouldReceive('info')->atLeast()->once();

        $job = new FetchArticlesJob('guardian', $category->slug, maxRetry: 3, jobTimeout: 300);
        $job->handle(app(ArticleRepositoryInterface::class));

        $this->assertDatabaseHas('articles', [
            'external_id' => 'guardian-article-1',
            'source_id'   => $guardianSource->id,
            'title'       => 'Guardian Title 1',
        ]);
        $this->assertDatabaseHas('articles', [
            'external_id' => 'guardian-article-2',
            'source_id'   => $guardianSource->id,
            'title'       => 'Guardian Title 2',
        ]);
    }



    /**
     * Test: NewsAPI as the source, ensures articles are stored.
     */
    public function test_fetch_articles_job_stores_articles_for_newsapi(): void
    {
        $newsApiSource = Source::factory()->create([
            'slug' => 'newsapi',
        ]);

        $category = Category::factory()->create([
            'slug' => 'world',
        ]);

        $fakeNewsApi = $this->createFakeSource('newsapi', [
            [
                'title'       => 'NewsAPI Title 1',
                'description' => 'NewsAPI Desc 1',
                'content'     => 'Some content 1',
                'url'         => 'https://example.com/newsapi1',
                'imageUrl'    => 'https://example.com/newsapi1.jpg',
                'author'      => 'NewsAPI Author 1',
                'publishedAt' => now()->subHours(2),
                'externalId'  => 'fake-hash-1',
                'category'    => $category->slug,
            ],
        ]);

        app()->bind('news.source.newsapi', fn() => $fakeNewsApi);

        $job = new FetchArticlesJob('newsapi', $category->slug, maxRetry: 2, jobTimeout: 120);
        $job->handle(app(ArticleRepositoryInterface::class));

        $this->assertDatabaseHas('articles', [
            'external_id' => 'fake-hash-1',
            'source_id'   => $newsApiSource->id,
            'title'       => 'NewsAPI Title 1',
        ]);
    }


    /**
     * Test: NYT as the source, ensures articles are stored.
     */
    public function test_fetch_articles_job_stores_articles_for_nyt(): void
    {
        $nytSource = Source::factory()->create([
            'slug' => 'nyt',
        ]);
        $category = Category::factory()->create([
            'slug' => 'business',
        ]);

        $fakeNyt = $this->createFakeSource('nyt', [
            [
                'title'       => 'NYT Title 1',
                'description' => 'NYT Abstract 1',
                'content'     => 'NYT Paragraph 1',
                'url'         => 'https://example.com/nyt1',
                'imageUrl'    => 'https://example.com/nyt1.jpg',
                'author'      => 'NYT Author 1',
                'publishedAt' => now()->subWeek(),
                'externalId'  => 'nyt-uri-123',
                'category'    => $category->slug,
            ],
        ]);

        app()->bind('news.source.nyt', fn() => $fakeNyt);

        $job = new FetchArticlesJob('nyt', $category->slug);
        $job->handle(app(ArticleRepositoryInterface::class));

        $this->assertDatabaseHas('articles', [
            'external_id' => 'nyt-uri-123',
            'source_id'   => $nytSource->id,
            'title'       => 'NYT Title 1',
        ]);
    }


    /**
     * Test: When the source throws a NewsSourceException, the job logs an error and rethrows it.
     */
    public function test_fetch_articles_job_throws_exception_if_source_fails(): void
    {
        $source = Source::factory()->create(['slug' => 'nyt']);
        $category = Category::factory()->create(['slug' => 'sports']);

        $failingSource = new class {
            public function fetchArticlesByCategory(string $category, int $limit = 100): Collection
            {
                throw new NewsSourceException('Simulated failure from NYT');
            }
        };

        app()->bind('news.source.nyt', fn() => $failingSource);

        Log::shouldReceive('info')->byDefault();
        Log::shouldReceive('error')->byDefault();

        Log::shouldReceive('error')->once()->withArgs(function ($message, $context) use ($source, $category) {
            return $message === 'Failed to fetch articles'
                && $context['source'] === $source->slug
                && $context['category'] === $category->slug
                && $context['error'] === 'Simulated failure from NYT';
        });

        $this->expectException(NewsSourceException::class);
        $this->expectExceptionMessage('Simulated failure from NYT');

        $job = new FetchArticlesJob('nyt', 'sports');
        $job->handle(app(ArticleRepositoryInterface::class));
    }



    /**
     * Test: If a category does not exist in the DB, the repository throws an InvalidArgumentException.
     */
    public function test_fetch_articles_job_logs_error_for_invalid_category_and_continues(): void
    {
        $source = Source::factory()->create([
            'slug' => 'newsapi',
        ]);

        $validCategory = Category::factory()->create([
            'slug' => 'technology',
        ]);

        $articlesData = [
            [
                'title'       => 'Valid Article',
                'description' => 'Description for valid article',
                'content'     => 'Some valid content',
                'url'         => 'https://example.com/valid',
                'imageUrl'    => null,
                'author'      => 'Author 1',
                'publishedAt' => now()->subDay(),
                'externalId'  => 'valid-article-123',
                'category'    => $validCategory->slug
            ],
            [
                'title'       => 'Invalid Category Article',
                'description' => 'Description for invalid category',
                'content'     => 'Some invalid content',
                'url'         => 'https://example.com/invalid',
                'imageUrl'    => null,
                'author'      => 'Author 2',
                'publishedAt' => now(),
                'externalId'  => 'invalid-article-456',
                'category'    => 'non-existent-category',
            ],
        ];

        $fakeNewsApi = $this->createFakeSource('newsapi', $articlesData);

        app()->bind('news.source.newsapi', fn() => $fakeNewsApi);

        Log::shouldReceive('info')->byDefault();

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'Failed to store article'
                    && $context['external_id'] === 'invalid-article-456'
                    && str_contains($context['error'], 'Category not found: non-existent-category');
            });

        $job = new FetchArticlesJob('newsapi', $validCategory->slug);
        $job->handle(app(\App\Contracts\Repositories\ArticleRepositoryInterface::class));

        $this->assertDatabaseHas('articles', [
            'external_id' => 'valid-article-123',
            'title'       => 'Valid Article',
            'source_id'   => $source->id,
        ]);

        $this->assertDatabaseMissing('articles', [
            'external_id' => 'invalid-article-456',
        ]);
    }




    /**
     * Helper: create a generic fake source for any identifier
     * that returns a collection of ArticleDTOs from the provided array data.
     *
     * @param  string  $sourceIdentifier  E.g. 'guardian', 'newsapi', 'nyt', ...
     * @param  array<int, array<string, mixed>>  $articlesData
     * @return object
     */
    private function createFakeSource(string $sourceIdentifier, array $articlesData): object
    {
        $articleDTOs = collect($articlesData)->map(function ($data) use ($sourceIdentifier) {
            return new ArticleDTO(
                title: $data['title'],
                description: $data['description'] ?? '',
                content: $data['content'] ?? null,
                url: $data['url'],
                imageUrl: $data['imageUrl'] ?? null,
                author: $data['author'] ?? null,
                publishedAt: $data['publishedAt'] ?? now(),
                externalId: $data['externalId'],
                category: $data['category'],
                sourceIdentifier: $sourceIdentifier,
            );
        });

        return new class($articleDTOs) {
            public function __construct(private Collection $articles) {}

            public function fetchArticlesByCategory(string $category, int $limit = 100): Collection
            {
                return $this->articles->take($limit);
            }
        };
    }
}
