<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Bus;
use App\Jobs\News\FetchArticlesJob;
use Illuminate\Bus\Batch;
use Throwable;

class FetchNewsCommand extends Command
{
    protected $signature = 'news:fetch 
        {--source=* : Specific sources to fetch from} 
        {--category=* : Specific categories to fetch} 
        {--max-retry=3 : Maximum number of retries for failed sources}
        {--timeout=300 : Timeout for news fetching in seconds}';

    protected $description = 'Fetch news from configured sources with enhanced error handling and performance';

    public function handle(): int
    {        
        $sources = $this->getSources();
        $categories = $this->getCategories();

        $this->validateConfiguration($sources, $categories);

        $this->info(sprintf(
            'Fetching news from %d sources for %d categories',
            $sources->count(),
            $categories->count()
        ));

        foreach ($sources as $source) {

            $startTime = microtime(true);
            $sourceIdentifier = $source->getSourceIdentifier();
            
            $this->info(sprintf(
                'Creating batch for source %s with %d categories',
                $sourceIdentifier,
                $categories->count()
            ));

            Bus::batch($this->prepareSourceJobs($source, $categories))
                ->name("News fetch: {$sourceIdentifier}")
                ->allowFailures()
                ->onQueue("news-{$sourceIdentifier}")
                ->then(fn (Batch $batch) => self::handleBatchSuccess($batch, $startTime))
                ->catch(fn (Batch $batch, Throwable $e) => self::handleBatchFailure($batch, $e))
                ->finally(fn (Batch $batch) => self::handleBatchCompletion($batch, $startTime))
                ->dispatch();
        }

        return self::SUCCESS;
    }

    /**
     * Validate that sources and categories are properly configured
     *
     * @param Collection $sources
     * @param Collection $categories
     * @throws \InvalidArgumentException
     */
    private function validateConfiguration(Collection $sources, Collection $categories): void
    {
        if ($sources->isEmpty()) {
            throw new \InvalidArgumentException('No news sources configured or available.');
        }

        if ($categories->isEmpty()) {
            throw new \InvalidArgumentException('No news categories configured.');
        }
    }

    /**
     * Prepare jobs for a specific source
     *
     * @param mixed $source
     * @param Collection $categories
     * @return array
     */
    private function prepareSourceJobs($source, Collection $categories): array
    {
        $jobs = [];
        $maxRetry = (int) $this->option('max-retry') ?: 3;
        $timeout = (int) $this->option('timeout') ?: 300;

        foreach ($categories as $category) {
            $jobs[] = new FetchArticlesJob(
                sourceIdentifier: $source->getSourceIdentifier(),
                category: $category->slug,
                maxRetry: $maxRetry,
                jobTimeout: $timeout
            );
        }

        return $jobs;
    }



    /**
     * Handle successful batch completion
     *
     * @param Batch $batch
     * @param float $startTime
     * @return void
     */
    private static function handleBatchSuccess(Batch $batch, float $startTime): void
    {
        Log::info('Batch processing completed', [
            'total_jobs' => $batch->totalJobs,
            'failed_jobs' => $batch->failedJobs,
            'processing_time' => round(microtime(true) - $startTime, 2) . ' seconds'
        ]);
    }


    /**
     * Handle batch failure
     *
     * @param Batch $batch
     * @param Throwable $e
     * @return void
     */
    private static function handleBatchFailure(Batch $batch, Throwable $e): void
    {
        Log::error('Batch processing failed', [
            'total_jobs' => $batch->totalJobs,
            'failed_jobs' => $batch->failedJobs,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }


    /**
     * Handle batch completion (runs after success or failure)
     *
     * @param Batch $batch
     * @param float $startTime
     * @return void
     */
    private static function handleBatchCompletion(Batch $batch, float $startTime): void
    {
        $processingTime = round(microtime(true) - $startTime, 2);
        
        Log::info('News Fetching Batch Summary', [
            'total_jobs' => $batch->totalJobs,
            'failed_jobs' => $batch->failedJobs,
            'processing_time' => $processingTime . ' seconds'
        ]);
    }


    /**
     * Get the news sources to process
     *
     * @return Collection
     */
    private function getSources(): Collection
    {
        $sources = app('news.sources');
        $requestedSources = $this->option('source');

        if (!empty($requestedSources)) {
            $sources = $sources->filter(
                fn ($source) => in_array($source->getSourceIdentifier(), $requestedSources)
            );
        }

        return $sources;
    }

    /**
     * Get the categories to process
     *
     * @return Collection
     */
    private function getCategories(): Collection
    {
        $query = Category::query();
        $requestedCategories = $this->option('category');

        if (!empty($requestedCategories)) {
            $query->whereIn('slug', $requestedCategories);
        }

        return $query->get();
    }

}
