<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Articles;

use App\Actions\Articles\SearchArticlesAction;
use App\Contracts\Repositories\ArticleRepositoryInterface;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Tests\TestCase;

class SearchArticlesActionTest extends TestCase
{
/**
     * Test search with user preferences
     */
    public function test_search_with_user_preferences(): void
    {
        $filters = ['keyword' => 'test'];
        $user = User::factory()->make();
        $perPage = 15;

        $mockPaginator = Mockery::mock(LengthAwarePaginator::class);

        $repository = Mockery::mock(ArticleRepositoryInterface::class);
        $repository->shouldReceive('searchArticles')
            ->once()
            ->with($filters, $user, $perPage)
            ->andReturn($mockPaginator);

        $action = new SearchArticlesAction($repository);
        $result = $action->execute($filters, $user, $perPage);

        $this->assertSame($mockPaginator, $result);
    }

    /**
     * Test search without user
     */
    public function test_search_without_user(): void
    {
        $filters = ['keyword' => 'test'];
        $perPage = 15;

        $mockPaginator = Mockery::mock(LengthAwarePaginator::class);

        $repository = Mockery::mock(ArticleRepositoryInterface::class);
        $repository->shouldReceive('searchArticles')
            ->once()
            ->with($filters, null, $perPage)
            ->andReturn($mockPaginator);

        $action = new SearchArticlesAction($repository);
        $result = $action->execute($filters, null, $perPage);

        $this->assertSame($mockPaginator, $result);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }
}
