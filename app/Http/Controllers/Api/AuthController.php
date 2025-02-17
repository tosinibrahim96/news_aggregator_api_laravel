<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Auth\LoginAction;
use App\Actions\Auth\LogoutAction;
use App\Actions\Auth\RefreshTokenAction;
use App\Actions\Auth\RegisterAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;


/**
 * @group Authentication
 * 
 * APIs for managing authentication
 */
class AuthController extends Controller
{
     /**
     * Register a new user
     * 
     * @param RegisterRequest $request
     * @param RegisterAction $action
     * @return JsonResponse
     * 
     * @bodyParam name string required The name of the user. Example: John Doe
     * @bodyParam email string required The email of the user. Example: john@example.com
     * @bodyParam password string required The password of the user. Example: password123
     * @bodyParam password_confirmation string required The password confirmation. Example: password123
     * 
     * @response 201 {
     *   "status": "success",
     *   "message": "Registration successful",
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "name": "John Doe",
     *       "email": "john@example.com",
     *       "created_at": "2024-02-16T12:00:00.000000Z"
     *     },
     *     "token": "abcdef123456",
     *     "type": "bearer"
     *   }
     * }
     */
    public function register(RegisterRequest $request, RegisterAction $action): JsonResponse
    {
        $result = $action->execute($request->validated());

        return ApiResponse::success([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
            'type' => 'bearer',
        ], 'Registration successful', 201);
    }

    /**
     * Login user
     * 
     * @param LoginRequest $request
     * @param LoginAction $action
     * @return JsonResponse
     * 
     * @bodyParam email string required The email of the user. Example: john@example.com
     * @bodyParam password string required The password of the user. Example: password123
     * 
     * @response 200 {
     *   "status": "success",
     *   "message": "Login successful",
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "name": "John Doe",
     *       "email": "john@example.com",
     *       "created_at": "2024-02-16T12:00:00.000000Z"
     *     },
     *     "token": "abcdef123456",
     *     "type": "bearer"
     *   }
     * }
     * 
     * @response 401 {
     *   "status": "error",
     *   "message": "Invalid credentials"
     * }
     */
    public function login(LoginRequest $request, LoginAction $action): JsonResponse
    {
        $result = $action->execute($request->validated());

        if (!$result) {
            return ApiResponse::error('Invalid credentials', null, 401);
        }

        return ApiResponse::success([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
            'type' => 'bearer',
        ], 'Login successful');
    }

    /**
     * Logout user
     * 
     * @param LogoutAction $action
     * @return JsonResponse
     * 
     * @authenticated
     * 
     * @response 200 {
     *   "status": "success",
     *   "message": "Logout successful",
     *   "data": null
     * }
     * 
     * @response 401 {
     *   "status": "error",
     *   "message": "Unauthenticated",
     *   "errors": null
     * }
     * 
     * @throws \Illuminate\Auth\AuthenticationException
     * 
     * @header Authorization Bearer {token}
     */
    public function logout(LogoutAction $action): JsonResponse
    {
        $action->execute();
        return ApiResponse::success(message: 'Logout successful');
    }


    /**
     * Refresh token
     * 
     * Refresh the JWT token for the authenticated user
     *
     * @param RefreshTokenAction $action
     * @return JsonResponse
     * 
     * @authenticated
     * 
     * @response 200 {
     *   "status": "success",
     *   "message": "Token refreshed successfully",
     *   "data": {
     *     "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
     *     "type": "bearer"
     *   }
     * }
     * 
     * @response 401 {
     *   "status": "error",
     *   "message": "Token has expired",
     *   "errors": null
     * }
     * 
     * @response 401 {
     *   "status": "error",
     *   "message": "Token is invalid",
     *   "errors": null
     * }
     * 
     * @throws \PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException
     * @throws \PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException
     * 
     * @header Authorization Bearer {token}
     */
    public function refresh(RefreshTokenAction $action): JsonResponse
    {
        return ApiResponse::success([
            'token' => $action->execute(),
            'type' => 'bearer',
        ], 'Token refreshed successfully');
    }
}
