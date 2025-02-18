<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Models\Category;
use App\Models\Source;
use App\Models\User;
use App\Models\UserPreference;
use App\Repositories\PreferenceRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreferenceRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private PreferenceRepository $repository;
    private User $user;
    private Source $source;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->repository = new PreferenceRepository(new UserPreference());
        $this->user = User::factory()->create();
        $this->source = Source::factory()->create();
        $this->category = Category::factory()->create();
    }

    /**
     * Test getting empty preferences
     */
    public function test_get_empty_preferences(): void
    {
        $preferences = $this->repository->getUserPreferences($this->user);

        $this->assertEmpty($preferences['sources']);
        $this->assertEmpty($preferences['categories']);
        $this->assertEmpty($preferences['authors']);
    }

    /**
     * Test getting existing preferences
     */
    public function test_get_existing_preferences(): void
    {
        UserPreference::factory()->forSource()->create([
            'user_id' => $this->user->id,
            'source_id' => $this->source->id
        ]);

        $preferences = $this->repository->getUserPreferences($this->user);

        $this->assertCount(1, $preferences['sources']);
        $this->assertEquals($this->source->slug, $preferences['sources'][0]);
    }

    /**
     * Test updating preferences
     */
    public function test_update_preferences(): void
    {
        $newPreferences = [
            'sources' => [$this->source->slug],
            'categories' => [$this->category->slug],
            'authors' => ['John Doe'],
        ];

        $result = $this->repository->updatePreferences($this->user, $newPreferences);

        $this->assertCount(1, $result['sources']);
        $this->assertCount(1, $result['categories']);
        $this->assertCount(1, $result['authors']);

        $this->assertEquals($this->source->slug, $result['sources'][0]);
        $this->assertEquals($this->category->slug, $result['categories'][0]);
        $this->assertEquals('John Doe', $result['authors'][0]);
    }

    /**
     * Test clearing preferences
     */
    public function test_clear_preferences(): void
    {
        UserPreference::factory()->forSource()->create([
            'user_id' => $this->user->id,
            'source_id' => $this->source->id
        ]);

        $result = $this->repository->updatePreferences($this->user, []);

        $this->assertEmpty($result['sources']);
        $this->assertEmpty($result['categories']);
        $this->assertEmpty($result['authors']);
        $this->assertDatabaseCount('user_preferences', 0);
    }

    /**
     * Test preferences transaction integrity
     */
    public function test_preferences_transaction_integrity(): void
    {
        UserPreference::factory()->forSource()->create([
            'user_id' => $this->user->id,
            'source_id' => $this->source->id
        ]);

        try {
            $this->repository->updatePreferences($this->user, [
                'sources' => [$this->source->slug, 'non-existent-source']
            ]);
            $this->fail('Expected exception was not thrown');
        } catch (\Exception $e) {
            $this->assertDatabaseCount('user_preferences', 1);
            $this->assertDatabaseHas('user_preferences', [
                'user_id' => $this->user->id,
                'source_id' => $this->source->id
            ]);
        }
    }
}
