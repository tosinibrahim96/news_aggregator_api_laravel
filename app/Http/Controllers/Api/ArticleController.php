<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Articles\SearchArticlesAction;
use App\Contracts\Repositories\ArticleRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Articles\SearchArticlesRequest;
use App\Http\Resources\ArticleResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="Articles",
 *     description="API Endpoints for articles"
 * )
 */
class ArticleController extends Controller
{
    public function __construct(
        private readonly ArticleRepositoryInterface $repository
    ) {}

    /**
     * Search and filter articles
     * 
     * @OA\Get(
     *     path="/api/articles/search",
     *     summary="Search and filter articles",
     *     description="Get a paginated list of articles with optional filtering and search. If user is authenticated and has preferences, results will be sorted accordingly.",
     *     operationId="searchArticles",
     *     tags={"Articles"},
     *     @OA\Parameter(
     *         name="keyword",
     *         in="query",
     *         description="Search term for article title and content",
     *         required=false,
     *         @OA\Schema(type="string", example="climate")
     *     ),
     *     @OA\Parameter(
     *         name="source",
     *         in="query",
     *         description="Filter by source slug",
     *         required=false,
     *         @OA\Schema(type="string", example="the-guardian")
     *     ),
     *     @OA\Parameter(
     *         name="category",
     *         in="query",
     *         description="Filter by category slug",
     *         required=false,
     *         @OA\Schema(type="string", example="technology")
     *     ),
     *     @OA\Parameter(
     *         name="author",
     *         in="query",
     *         description="Filter by author name",
     *         required=false,
     *         @OA\Schema(type="string", example="John Doe")
     *     ),
     *     @OA\Parameter(
     *         name="date_from",
     *         in="query",
     *         description="Filter articles published after this date",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2024-01-01")
     *     ),
     *     @OA\Parameter(
     *         name="date_to",
     *         in="query",
     *         description="Filter articles published before this date",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2024-02-01")
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Sort field and direction (-published_at, published_at, -title, title)",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             enum={"-published_at", "published_at", "-title", "title"},
     *             example="-published_at"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of articles per page",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=100, example=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="title", type="string", example="Article Title"),
     *                         @OA\Property(property="description", type="string", example="Article description"),
     *                         @OA\Property(property="content", type="string", example="Article content"),
     *                         @OA\Property(property="url", type="string", example="https://example.com/article"),
     *                         @OA\Property(property="image_url", type="string", example="https://example.com/image.jpg"),
     *                         @OA\Property(property="author", type="string", example="John Doe"),
     *                         @OA\Property(property="published_at", type="string", format="date-time", example="2024-02-17T12:00:00.000000Z"),
     *                         @OA\Property(
     *                             property="source",
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="name", type="string", example="The Guardian"),
     *                             @OA\Property(property="slug", type="string", example="the-guardian")
     *                         ),
     *                         @OA\Property(
     *                             property="category",
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="name", type="string", example="Technology"),
     *                             @OA\Property(property="slug", type="string", example="technology")
     *                         )
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="meta",
     *                     type="object",
     *                     @OA\Property(property="current_page", type="integer", example=1),
     *                     @OA\Property(property="from", type="integer", example=1),
     *                     @OA\Property(property="last_page", type="integer", example=10),
     *                     @OA\Property(property="per_page", type="integer", example=15),
     *                     @OA\Property(property="to", type="integer", example=15),
     *                     @OA\Property(property="total", type="integer", example=150)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation Error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\AdditionalProperties(
     *                     type="array",
     *                     @OA\Items(type="string")
     *                 )
     *             )
     *         )
     *     )
     * )
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
