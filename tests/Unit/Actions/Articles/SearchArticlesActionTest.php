<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Articles;

use App\Actions\Articles\SearchArticlesAction;
use App\Contracts\Repositories\ArticleRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Tests\TestCase;

class SearchArticlesActionTest extends TestCase
{
    /**
     * Test search articles action executes successfully
     */
    public function test_search_articles_action_executes_successfully(): void
    {
        $filters = ['keyword' => 'test'];
        $perPage = 15;

        $mockPaginator = Mockery::mock(LengthAwarePaginator::class);

        $repository = Mockery::mock(ArticleRepositoryInterface::class);
        $repository->shouldReceive('searchArticles')
            ->once()
            ->with($filters, $perPage)
            ->andReturn($mockPaginator);

        $action = new SearchArticlesAction($repository);
        $result = $action->execute($filters, $perPage);

        $this->assertSame($mockPaginator, $result);
    }

    /**
     * Test search articles action with default per page
     */
    public function test_search_articles_action_uses_default_per_page(): void
    {
        $filters = ['keyword' => 'test'];
        $defaultPerPage = 15;

        $mockPaginator = Mockery::mock(LengthAwarePaginator::class);

        $repository = Mockery::mock(ArticleRepositoryInterface::class);
        $repository->shouldReceive('searchArticles')
            ->once()
            ->with($filters, $defaultPerPage)
            ->andReturn($mockPaginator);

        $action = new SearchArticlesAction($repository);
        $result = $action->execute($filters);

        $this->assertSame($mockPaginator, $result);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }
}

namespace Tests\Unit\Repositories;

use App\Models\Article;
use App\Models\Category;
use App\Models\Source;
use App\Repositories\ArticleRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ArticleRepository $repository;
    private Source $source;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->repository = new ArticleRepository(new Article());
        $this->source = Source::factory()->create();
        $this->category = Category::factory()->create();
    }

    /**
     * Test searching with keyword
     */
    public function test_search_with_keyword(): void
    {
        // Create article with matching title
        Article::factory()->create([
            'source_id' => $this->source->id,
            'category_id' => $this->category->id,
            'title' => 'Test Article',
        ]);

        // Create article without matching title
        Article::factory()->create([
            'source_id' => $this->source->id,
            'category_id' => $this->category->id,
            'title' => 'Another Article',
        ]);

        $result = $this->repository->searchArticles(['keyword' => 'Test']);

        $this->assertEquals(1, $result->total());
        $this->assertEquals('Test Article', $result->items()[0]->title);
    }

    /**
     * Test filtering by source
     */
    public function test_filter_by_source(): void
    {
        $anotherSource = Source::factory()->create();

        Article::factory()->create([
            'source_id' => $this->source->id,
            'category_id' => $this->category->id,
        ]);

        Article::factory()->create([
            'source_id' => $anotherSource->id,
            'category_id' => $this->category->id,
        ]);

        $result = $this->repository->searchArticles(['source' => $this->source->slug]);

        $this->assertEquals(1, $result->total());
        $this->assertEquals($this->source->id, $result->items()[0]->source_id);
    }

    /**
     * Test complex filtering
     */
    public function test_complex_filtering(): void
    {
        // Create article matching all filters
        Article::factory()->create([
            'source_id' => $this->source->id,
            'category_id' => $this->category->id,
            'author' => 'John Doe',
            'published_at' => '2024-01-15',
            'title' => 'Test Article',
        ]);

        // Create non-matching articles
        Article::factory()->count(3)->create([
            'source_id' => $this->source->id,
            'category_id' => $this->category->id,
        ]);

        $filters = [
            'keyword' => 'Test',
            'source' => $this->source->slug,
            'category' => $this->category->slug,
            'author' => 'John Doe',
            'date_from' => '2024-01-01',
            'date_to' => '2024-01-31',
        ];

        $result = $this->repository->searchArticles($filters);

        $this->assertEquals(1, $result->total());
        $this->assertEquals('Test Article', $result->items()[0]->title);
    }
}
