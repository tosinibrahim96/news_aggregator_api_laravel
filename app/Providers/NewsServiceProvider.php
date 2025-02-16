<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Source;
use App\Services\News\Sources\GuardianNewsSource;
use App\Services\News\Sources\NewsApiSource;
use App\Services\News\Sources\NytNewsSource;
use Illuminate\Support\ServiceProvider;

class NewsServiceProvider extends ServiceProvider
{
    /**
     * Register news-related services
     */
    public function register(): void
    {
        $this->app->bind('news.source.the-guardian', function ($app) {
            return new GuardianNewsSource(
                Source::where('slug', 'the-guardian')->firstOrFail()
            );
        });

        $this->app->bind('news.source.newsapi', function ($app) {
            return new NewsApiSource(
                Source::where('slug', 'newsapi')->firstOrFail()
            );
        });

        $this->app->bind('news.source.new-york-times', function ($app) {
            return new NytNewsSource(
                Source::where('slug', 'new-york-times')->firstOrFail()
            );
        });

        $this->app->bind('news.sources', function ($app) {
            return collect([
                $app->make('news.source.the-guardian'),
                $app->make('news.source.newsapi'),
                $app->make('news.source.new-york-times'),
            ]);
        });
    }

    /**
     * Bootstrap news-related services
     */
    public function boot(): void
    {
        //
    }
}
