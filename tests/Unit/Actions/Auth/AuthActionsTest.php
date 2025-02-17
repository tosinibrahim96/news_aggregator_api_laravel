<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Auth;

use App\Actions\Auth\LoginAction;
use App\Actions\Auth\LogoutAction;
use App\Actions\Auth\RefreshTokenAction;
use App\Actions\Auth\RegisterAction;
use App\Contracts\Repositories\AuthRepositoryInterface;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Mockery;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class AuthActionsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test register action creates user and returns token
     */
    public function test_register_action_creates_user_and_returns_token(): void
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ];

        $user = User::factory()->create($userData);

        $repository = Mockery::mock(AuthRepositoryInterface::class);
        $repository->shouldReceive('createUser')
            ->once()
            ->with($userData)
            ->andReturn($user);

        $action = new RegisterAction($repository);
        $result = $action->execute($userData);

        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('token', $result);
        $this->assertEquals($user, $result['user']);
        $this->assertIsString($result['token']);
    }

    /**
     * Test login action with valid credentials
     */
    public function test_login_action_returns_user_and_token_for_valid_credentials(): void
    {
        $user = User::factory()->create();
        
        $credentials = [
            'email' => $user->email,
            'password' => 'password'
        ];

        $action = new LoginAction($this->app->make(AuthRepositoryInterface::class));
        $result = $action->execute($credentials);

        $this->assertNotNull($result);
        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('token', $result);
        $this->assertEquals($user->id, $result['user']->id);
        $this->assertIsString($result['token']);
    }

    /**
     * Test login action with invalid credentials
     */
    public function test_login_action_returns_null_for_invalid_credentials(): void
    {
        $user = User::factory()->create();
        
        $credentials = [
            'email' => $user->email,
            'password' => 'wrong-password'
        ];

        $action = new LoginAction($this->app->make(AuthRepositoryInterface::class));
        $result = $action->execute($credentials);

        $this->assertNull($result);
    }

    /**
     * Test logout action
     */
    public function test_logout_action(): void
    {
        $user = User::factory()->create();
        Auth::login($user);

        $action = new LogoutAction();
        $action->execute();

        $this->assertNull(Auth::user());
    }

    /**
     * Test refresh token action
     */
    public function test_refresh_token_action(): void
    {
        $user = User::factory()->create();
        Auth::login($user);
        $oldToken = JWTAuth::getToken();

        $action = new RefreshTokenAction();
        $newToken = $action->execute();

        $this->assertNotEquals($oldToken, $newToken);
        $this->assertIsString($newToken);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }
}
