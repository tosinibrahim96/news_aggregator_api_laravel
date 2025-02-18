<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Preferences;

use App\Actions\Preferences\GetUserPreferencesAction;
use App\Actions\Preferences\UpdateUserPreferencesAction;
use App\Contracts\Repositories\PreferenceRepositoryInterface;
use App\Models\User;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

class PreferenceActionsTest extends TestCase
{
    /**
     * Test get preferences action
     */
    public function test_get_preferences_action(): void
    {
        $user = User::factory()->create();
        $expectedPreferences = [
            'sources' => collect(['the-guardian']),
            'categories' => collect(['technology']),
            'authors' => collect(['John Doe']),
        ];

        $repository = Mockery::mock(PreferenceRepositoryInterface::class);
        $repository->shouldReceive('getUserPreferences')
            ->once()
            ->with($user)
            ->andReturn($expectedPreferences);

        $action = new GetUserPreferencesAction($repository);
        $result = $action->execute($user);

        $this->assertEquals($expectedPreferences, $result);
    }

    /**
     * Test update preferences action
     */
    public function test_update_preferences_action(): void
    {
        $user = User::factory()->create();
        $newPreferences = [
            'sources' => ['the-guardian'],
            'categories' => ['technology'],
            'authors' => ['John Doe'],
        ];

        $expectedResult = [
            'sources' => collect(['the-guardian']),
            'categories' => collect(['technology']),
            'authors' => collect(['John Doe']),
        ];

        $repository = Mockery::mock(PreferenceRepositoryInterface::class);
        $repository->shouldReceive('updatePreferences')
            ->once()
            ->with($user, $newPreferences)
            ->andReturn($expectedResult);

        $action = new UpdateUserPreferencesAction($repository);
        $result = $action->execute($user, $newPreferences);

        $this->assertEquals($expectedResult, $result);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }
}

