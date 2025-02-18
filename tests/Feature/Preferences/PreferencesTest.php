<?php

declare(strict_types=1);

namespace Tests\Feature\Preferences;

use App\Models\Category;
use App\Models\Source;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreferencesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Source $source;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->source = Source::factory()->create();
        $this->category = Category::factory()->create();
    }

    /**
     * Test unauthenticated user cannot access preferences
     */
    public function test_unauthenticated_user_cannot_access_preferences(): void
    {
        $response = $this->getJson('/api/preferences');
        $response->assertUnauthorized();

        $response = $this->putJson('/api/preferences');
        $response->assertUnauthorized();
    }

    /**
     * Test user can get their preferences when none exist
     */
    public function test_user_can_get_empty_preferences(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/preferences');

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'sources' => [],
                    'categories' => [],
                    'authors' => []
                ]
            ]);
    }

    /**
     * Test user can get their existing preferences
     */
    public function test_user_can_get_existing_preferences(): void
    {
        // Create preferences
        UserPreference::factory()->forSource()->create([
            'user_id' => $this->user->id,
            'source_id' => $this->source->id
        ]);

        UserPreference::factory()->forCategory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id
        ]);

        UserPreference::factory()->forAuthor()->create([
            'user_id' => $this->user->id,
            'author_name' => 'John Doe'
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/preferences');

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'sources' => [$this->source->slug],
                    'categories' => [$this->category->slug],
                    'authors' => ['John Doe']
                ]
            ]);
    }

    /**
     * Test user can update their preferences
     */
    public function test_user_can_update_preferences(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/preferences', [
                'sources' => [$this->source->slug],
                'categories' => [$this->category->slug],
                'authors' => ['John Doe']
            ]);

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'message' => 'Preferences updated successfully',
                'data' => [
                    'sources' => [$this->source->slug],
                    'categories' => [$this->category->slug],
                    'authors' => ['John Doe']
                ]
            ]);

        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $this->user->id,
            'source_id' => $this->source->id
        ]);

        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $this->user->id,
            'category_id' => $this->category->id
        ]);

        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $this->user->id,
            'author_name' => 'John Doe'
        ]);
    }

    /**
     * Test user can clear their preferences
     */
    public function test_user_can_clear_preferences(): void
    {
        UserPreference::factory()->forSource()->create([
            'user_id' => $this->user->id,
            'source_id' => $this->source->id
        ]);

        $response = $this->actingAs($this->user)
            ->putJson('/api/preferences', [
                'sources' => [],
                'categories' => [],
                'authors' => []
            ]);

        $response->assertOk();
        $this->assertDatabaseCount('user_preferences', 0);
    }

    /**
     * Test validation of invalid preferences
     */
    public function test_preference_validation(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/preferences', [
                'sources' => ['invalid-source'],
                'categories' => ['invalid-category'],
                'authors' => [str_repeat('a', 101)] // Too long author name
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sources.0', 'categories.0', 'authors.0']);
    }

    /**
     * Test preferences are isolated between users
     */
    public function test_preferences_are_isolated_between_users(): void
    {
        $otherUser = User::factory()->create();

        UserPreference::factory()->forSource()->create([
            'user_id' => $otherUser->id,
            'source_id' => $this->source->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/preferences');

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'sources' => [],
                    'categories' => [],
                    'authors' => []
                ]
            ]);
    }
}
