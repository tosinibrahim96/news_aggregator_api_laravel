<?php

declare(strict_types=1);

namespace App\Jobs\News;

use App\Contracts\Repositories\ArticleRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Exceptions\NewsSourceException;
use Illuminate\Bus\Batchable;
use Throwable;

class FetchArticlesJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly string $sourceIdentifier,
        private readonly string $category,
        private readonly int $maxRetry = 3,
        private readonly int $jobTimeout = 300
    ) {
        $this->tries = $maxRetry;
        $this->timeout = $jobTimeout;
        $this->queue = "news-{$sourceIdentifier}";
    }

    /**
     * Execute the job.
     */
    public function handle(ArticleRepositoryInterface $repository): void
    {
        try {
            $source = app("news.source.{$this->sourceIdentifier}");

            Log::info(sprintf(
                'Fetching %s articles from %s',
                $this->category,
                $this->sourceIdentifier
            ));

            $articles = $source->fetchArticlesByCategory($this->category);

            Log::info(sprintf(
                'Fetched %d articles from %s in category %s',
                $articles->count(),
                $this->sourceIdentifier,
                $this->category
            ));

            $stats = $repository->storeArticles($articles, $this->sourceIdentifier);

            Log::info('Article storage completed', [
                'source' => $this->sourceIdentifier,
                'category' => $this->category,
                'stats' => $stats,
            ]);


        } catch (NewsSourceException $e) {
            Log::error('Failed to fetch articles', [
                'source' => $this->sourceIdentifier,
                'category' => $this->category,
                'error' => $e->getMessage(),
                'attempts' => $this->attempts()
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::critical('News fetching job completely failed', [
            'source' => $this->sourceIdentifier,
            'category' => $this->category,
            'error' => $exception->getMessage()
        ]);
    }
}
