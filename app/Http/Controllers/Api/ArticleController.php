<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Articles\SearchArticlesAction;
use App\Contracts\Repositories\ArticleRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Articles\SearchArticlesRequest;
use App\Http\Resources\ArticleResource;
use App\Http\Responses\ApiResponse;
use App\Models\Article;
use App\Models\Category;
use App\Models\Source;
use Illuminate\Http\JsonResponse;

/**
 * @group Articles
 */
class ArticleController extends Controller
{
    public function __construct(
        private readonly ArticleRepositoryInterface $repository
    ) {}

    /**
     * Search and filter articles
     *
     * Get a paginated list of articles with optional filtering and search
     *
     * @param SearchArticlesRequest $request
     * @param SearchArticlesAction $action
     * @return JsonResponse
     *
     * @queryParam keyword string Search term for article title and content. Example: climate
     * @queryParam source string Filter by source slug. Example: the-guardian
     * @queryParam category string Filter by category slug. Example: technology
     * @queryParam author string Filter by author name. Example: John Doe
     * @queryParam date_from date Filter articles published after this date. Example: 2024-01-01
     * @queryParam date_to date Filter articles published before this date. Example: 2024-02-01
     * @queryParam sort_by string Sort field and direction (-published_at, published_at, -title, title). Example: -published_at
     * @queryParam per_page integer Number of articles per page (1-100). Example: 15
     */
    public function search(SearchArticlesRequest $request, SearchArticlesAction $action): JsonResponse
    {
        $articles = $action->execute(
            filters: $request->validated(),
            perPage: (int) $request->input('per_page', 15)
        );

        return ApiResponse::success(
            ArticleResource::collection($articles)->response()->getData()
        );
    }
}
