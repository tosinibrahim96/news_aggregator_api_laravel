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
 * @group User Preferences
 * 
 * APIs for managing user preferences for personalized news feed
 */
class PreferencesController extends Controller
{
    /**
     * Get user preferences
     *
     * Retrieve the current user's preferences for sources, categories, and authors
     * 
     * @param GetUserPreferencesAction $action
     * @return JsonResponse
     * 
     * @authenticated
     * 
     * @response {
     *   "status": "success",
     *   "data": {
     *     "sources": ["the-guardian", "nyt"],
     *     "categories": ["technology", "science"],
     *     "authors": ["John Doe", "Jane Smith"]
     *   }
     * }
     */
    public function index(GetUserPreferencesAction $action): JsonResponse
    {
        return ApiResponse::success(
            $action->execute(auth()->user())
        );
    }

    /**
     * Update user preferences
     *
     * Update the current user's preferences for sources, categories, and authors
     * 
     * @param UpdatePreferencesRequest $request
     * @param UpdateUserPreferencesAction $action
     * @return JsonResponse
     * 
     * @authenticated
     * 
     * @bodyParam sources array Array of source slugs. Example: ["the-guardian", "nyt"]
     * @bodyParam categories array Array of category slugs. Example: ["technology", "science"]
     * @bodyParam authors array Array of author names. Example: ["John Doe", "Jane Smith"]
     * 
     * @response {
     *   "status": "success",
     *   "message": "Preferences updated successfully",
     *   "data": {
     *     "sources": ["the-guardian", "nyt"],
     *     "categories": ["technology", "science"],
     *     "authors": ["John Doe", "Jane Smith"]
     *   }
     * }
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
