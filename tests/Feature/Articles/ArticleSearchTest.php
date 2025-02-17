<?php

declare(strict_types=1);

namespace Tests\Feature\Articles;

use App\Models\Article;
use App\Models\Category;
use App\Models\Source;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleSearchTest extends TestCase
{
    use RefreshDatabase;

    private Source $source;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->source = Source::factory()->create(['name' => 'Test Source']);
        $this->category = Category::factory()->create(['name' => 'Technology']);
    }

    /**
     * Test basic article search without filters
     */
    public function test_can_search_articles_without_filters(): void
    {
        Article::factory()->count(15)->create([
            'source_id' => $this->source->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->getJson('/api/articles/search');

        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'title',
                            'description',
                            'content',
                            'url',
                            'image_url',
                            'author',
                            'published_at',
                            'source' => [
                                'id',
                                'name',
                                'slug',
                            ],
                            'category' => [
                                'id',
                                'name',
                                'slug',
                            ],
                        ],
                    ],
                    'meta' => [
                        'current_page',
                        'from',
                        'last_page',
                        'per_page',
                        'to',
                        'total',
                    ],
                ],
            ]);
    }

    /**
     * Test keyword search
     */
    public function test_can_search_articles_by_keyword(): void
    {
        // Create articles with specific title
        Article::factory()->count(3)->create([
            'source_id' => $this->source->id,
            'category_id' => $this->category->id,
            'title' => 'AI Development News',
        ]);

        // Create other articles
        Article::factory()->count(5)->create([
            'source_id' => $this->source->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->getJson('/api/articles/search?keyword=AI Development');

        $response->assertOk();
        $this->assertEquals(3, $response->json('data.meta.total'));
        $this->assertStringContainsString('AI Development', $response->json('data.data.0.title'));
    }

    /**
     * Test source filtering
     */
    public function test_can_filter_articles_by_source(): void
    {
        $anotherSource = Source::factory()->create();

        // Create articles for our test source
        Article::factory()->count(3)->create([
            'source_id' => $this->source->id,
            'category_id' => $this->category->id,
        ]);

        // Create articles for another source
        Article::factory()->count(5)->create([
            'source_id' => $anotherSource->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->getJson("/api/articles/search?source={$this->source->slug}");

        $response->assertOk();
        $this->assertEquals(3, $response->json('data.meta.total'));
        $this->assertEquals($this->source->id, $response->json('data.data.0.source.id'));
    }

    /**
     * Test category filtering
     */
    public function test_can_filter_articles_by_category(): void
    {
        $anotherCategory = Category::factory()->create();

        // Create articles for our test category
        Article::factory()->count(3)->create([
            'source_id' => $this->source->id,
            'category_id' => $this->category->id,
        ]);

        // Create articles for another category
        Article::factory()->count(5)->create([
            'source_id' => $this->source->id,
            'category_id' => $anotherCategory->id,
        ]);

        $response = $this->getJson("/api/articles/search?category={$this->category->slug}");

        $response->assertOk();
        $this->assertEquals(3, $response->json('data.meta.total'));
        $this->assertEquals($this->category->id, $response->json('data.data.0.category.id'));
    }

    /**
     * Test author filtering
     */
    public function test_can_filter_articles_by_author(): void
    {
        // Create articles with specific author
        Article::factory()->count(3)->create([
            'source_id' => $this->source->id,
            'category_id' => $this->category->id,
            'author' => 'John Doe',
        ]);

        // Create other articles
        Article::factory()->count(5)->create([
            'source_id' => $this->source->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->getJson('/api/articles/search?author=John Doe');

        $response->assertOk();
        $this->assertEquals(3, $response->json('data.meta.total'));
        $this->assertEquals('John Doe', $response->json('data.data.0.author'));
    }

    /**
     * Test date range filtering
     */
    public function test_can_filter_articles_by_date_range(): void
    {
        // Create articles with specific dates
        Article::factory()->count(3)->create([
            'source_id' => $this->source->id,
            'category_id' => $this->category->id,
            'published_at' => '2024-01-15',
        ]);

        Article::factory()->count(5)->create([
            'source_id' => $this->source->id,
            'category_id' => $this->category->id,
            'published_at' => '2024-02-15',
        ]);

        $response = $this->getJson('/api/articles/search?date_from=2024-01-01&date_to=2024-01-31');

        $response->assertOk();
        $this->assertEquals(3, $response->json('data.meta.total'));
    }

    /**
     * Test pagination
     */
    public function test_articles_are_properly_paginated(): void
    {
        Article::factory()->count(30)->create([
            'source_id' => $this->source->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->getJson('/api/articles/search?per_page=10');

        $response->assertOk();
        $this->assertEquals(10, count($response->json('data.data')));
        $this->assertEquals(30, $response->json('data.meta.total'));
        $this->assertEquals(3, $response->json('data.meta.last_page'));
    }

    /**
     * Test sorting
     */
    public function test_articles_can_be_sorted(): void
    {
        Article::factory()->create([
            'source_id' => $this->source->id,
            'category_id' => $this->category->id,
            'title' => 'A Title',
            'published_at' => '2024-01-01',
        ]);

        Article::factory()->create([
            'source_id' => $this->source->id,
            'category_id' => $this->category->id,
            'title' => 'Z Title',
            'published_at' => '2024-02-01',
        ]);

        $response = $this->getJson('/api/articles/search?sort_by=title');
        $this->assertEquals('A Title', $response->json('data.data.0.title'));

        $response = $this->getJson('/api/articles/search?sort_by=-title');
        $this->assertEquals('Z Title', $response->json('data.data.0.title'));

        $response = $this->getJson('/api/articles/search?sort_by=-published_at');
        $this->assertEquals('2024-02-01', substr($response->json('data.data.0.published_at'), 0, 10));
    }

    /**
     * Test invalid filter validation
     */
    public function test_invalid_filters_return_validation_errors(): void
    {
        $response = $this->getJson('/api/articles/search?date_from=invalid-date');
        $response->assertStatus(422);

        $response = $this->getJson('/api/articles/search?per_page=1000');
        $response->assertStatus(422);

        $response = $this->getJson('/api/articles/search?sort_by=invalid-field');
        $response->assertStatus(422);
    }
}
