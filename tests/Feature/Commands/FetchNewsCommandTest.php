<?php

namespace Tests\Feature\Commands;

use Tests\TestCase;
use Mockery;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Category;

class FetchNewsCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: throws error when no sources are configured or available.
     */
    public function test_command_fails_if_no_sources_configured(): void
    {
        app()->bind('news.sources', fn () => collect());

        Category::factory()->create();

        $this->expectExceptionMessage('No news sources configured or available.');

        Artisan::call('news:fetch');
    }

    /**
     * Test: throws error when no categories exist or are configured.
     */
    public function test_command_fails_if_no_categories_configured(): void
    {
        $mockSource = $this->createMockSource('mock-source-1');
        app()->bind('news.sources', fn () => collect([$mockSource]));

        $this->expectExceptionMessage('No news categories configured.');

        Artisan::call('news:fetch');
    }

    /**
     * Test: command fetches sources and categories, dispatching a batch for each source.
     */
    public function test_command_dispatches_batches_for_each_source(): void
    {
        Bus::fake();

        $mockSource1 = $this->createMockSource('mock-source-1');
        $mockSource2 = $this->createMockSource('mock-source-2');
        app()->bind('news.sources', fn () => collect([$mockSource1, $mockSource2]));

        $categories = Category::factory()->count(2)->create();

        Artisan::call('news:fetch');

        $batches = Bus::dispatchedBatches();

        $this->assertCount(2, $batches, 'Expected exactly 2 batches to be dispatched');

        foreach ($batches as $batchIndex => $batch) {
            $this->assertCount(
                $categories->count(),
                $batch->jobs,
                "Batch #{$batchIndex} did not have the expected number of jobs"
            );
        }
    }

    /**
     * Test: command respects the --source option, fetching only specified sources.
     */
    public function test_command_with_source_option_fetches_only_specified_sources(): void
    {
        Bus::fake();

        $mockSource1 = $this->createMockSource('guardian');
        $mockSource2 = $this->createMockSource('nyt');
        $mockSource3 = $this->createMockSource('newsapi');

        app()->bind('news.sources', fn () => collect([$mockSource1, $mockSource2, $mockSource3]));

        Category::factory()->count(1)->create();

        Artisan::call('news:fetch', [
            '--source' => ['guardian', 'nyt']
        ]);

        $batches = Bus::dispatchedBatches();
        $this->assertCount(
            2,
            $batches,
            'Expected 2 batches total when requesting guardian and nyt only'
        );
    }

    /**
     * Test: command respects the --category option, fetching only specified categories.
     */
    public function test_command_with_category_option_fetches_only_specified_categories(): void
    {
        Bus::fake();

        $mockSource = $this->createMockSource('guardian');
        app()->bind('news.sources', fn () => collect([$mockSource]));

        $cat1 = Category::factory()->create(['slug' => 'politics']);
        $cat2 = Category::factory()->create(['slug' => 'technology']);
        $cat3 = Category::factory()->create(['slug' => 'sports']);

        Artisan::call('news:fetch', [
            '--category' => ['politics', 'technology']
        ]);

        $batches = Bus::dispatchedBatches();
        $this->assertCount(1, $batches, 'We expected exactly 1 batch for 1 source');

        $this->assertCount(2, $batches[0]->jobs);
    }

    /**
     * Helper: Create a mock source object with getSourceIdentifier() method.
     */
    private function createMockSource(string $identifier)
    {
        $mock = Mockery::mock();
        $mock->shouldReceive('getSourceIdentifier')->andReturn($identifier);
        return $mock;
    }
}
