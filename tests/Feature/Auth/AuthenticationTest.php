<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test user registration
     */
    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'created_at'
                    ],
                    'token',
                    'type'
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'name' => 'Test User'
        ]);
    }

    /**
     * Test user login
     */
    public function test_user_can_login(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password'
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'created_at'
                    ],
                    'token',
                    'type'
                ]
            ]);
    }

    /**
     * Test invalid login credentials
     */
    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password'
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'status' => 'error',
                'message' => 'Invalid credentials'
            ]);
    }

    /**
     * Test user logout
     */
    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/auth/logout');

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'message' => 'Logout successful'
            ]);

        // Try to use the token after logout
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/auth/logout');

        $response->assertStatus(401);
    }

    /**
     * Test token refresh
     */
    public function test_user_can_refresh_token(): void
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/auth/refresh');

        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'token',
                    'type'
                ]
            ]);

        $this->assertNotEquals($token, $response->json('data.token'));
    }

    /**
     * Test token expiration
     */
    public function test_expired_token_cannot_access_protected_routes(): void
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        // Simulate time jump
        Carbon::setTestNow(now()->addMinutes(config('jwt.ttl') + 1));

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/auth/logout');

        $response->assertStatus(401);

        Carbon::setTestNow();
    }
}
