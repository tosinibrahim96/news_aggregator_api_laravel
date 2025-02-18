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
     * Get a paginated list of articles with optional filtering and search.
     * If user is authenticated and has preferences, results will be sorted accordingly.
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
     *
     * @response {
     *   "status": "success",
     *   "data": {
     *     "data": [
     *       {
     *         "id": 1,
     *         "title": "Article Title",
     *         "description": "Article description",
     *         "content": "Article content",
     *         "url": "https://example.com/article",
     *         "image_url": "https://example.com/image.jpg",
     *         "author": "John Doe",
     *         "published_at": "2024-02-17T12:00:00.000000Z",
     *         "source": {
     *           "id": 1,
     *           "name": "The Guardian",
     *           "slug": "the-guardian"
     *         },
     *         "category": {
     *           "id": 1,
     *           "name": "Technology",
     *           "slug": "technology"
     *         }
     *       }
     *     ],
     *     "meta": {
     *       "current_page": 1,
     *       "from": 1,
     *       "last_page": 10,
     *       "per_page": 15,
     *       "to": 15,
     *       "total": 150
     *     }
     *   }
     * }
     */
    public function search(SearchArticlesRequest $request, SearchArticlesAction $action): JsonResponse
    {
        $articles = $action->execute(
            filters: $request->validated(),
            user: auth()->user(),
            perPage: (int) $request->input('per_page', 15)
        );

        return ApiResponse::success(
            ArticleResource::collection($articles)->response()->getData()
        );
    }
}
