<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Preferences\GetUserPreferencesAction;
use App\Actions\Preferences\UpdateUserPreferencesAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Preferences\UpdatePreferencesRequest;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="User Preferences",
 *     description="API Endpoints for managing user preferences for personalized news feed"
 * )
 */
class PreferencesController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/preferences",
     *     summary="Get user preferences",
     *     description="Retrieve the current user's preferences for sources, categories, and authors",
     *     operationId="getPreferences",
     *     tags={"User Preferences"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Preferences retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="sources",
     *                     type="array",
     *                     @OA\Items(type="string", example="the-guardian")
     *                 ),
     *                 @OA\Property(
     *                     property="categories",
     *                     type="array",
     *                     @OA\Items(type="string", example="technology")
     *                 ),
     *                 @OA\Property(
     *                     property="authors",
     *                     type="array",
     *                     @OA\Items(type="string", example="John Doe")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function index(GetUserPreferencesAction $action): JsonResponse
    {
        return ApiResponse::success(
            $action->execute(auth()->user())
        );
    }

    /**
     * @OA\Put(
     *     path="/api/preferences",
     *     summary="Update user preferences",
     *     description="Update the current user's preferences for sources, categories, and authors",
     *     operationId="updatePreferences",
     *     tags={"User Preferences"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="sources",
     *                 type="array",
     *                 @OA\Items(type="string", example="the-guardian")
     *             ),
     *             @OA\Property(
     *                 property="categories",
     *                 type="array",
     *                 @OA\Items(type="string", example="technology")
     *             ),
     *             @OA\Property(
     *                 property="authors",
     *                 type="array",
     *                 @OA\Items(type="string", example="John Doe")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Preferences updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Preferences updated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="sources",
     *                     type="array",
     *                     @OA\Items(type="string", example="the-guardian")
     *                 ),
     *                 @OA\Property(
     *                     property="categories",
     *                     type="array",
     *                     @OA\Items(type="string", example="technology")
     *                 ),
     *                 @OA\Property(
     *                     property="authors",
     *                     type="array",
     *                     @OA\Items(type="string", example="John Doe")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation Error"
     *     )
     * )
     */
    public function update(
        UpdatePreferencesRequest $request,
        UpdateUserPreferencesAction $action
    ): JsonResponse {
        return ApiResponse::success(
            $action->execute(auth()->user(), $request->validated()),
            'Preferences updated successfully'
        );
    }
}
