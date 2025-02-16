<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Repositories\ArticleRepositoryInterface;
use App\Repositories\ArticleRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register the repository bindings.
     */
    public function register(): void
    {
        $this->app->bind(ArticleRepositoryInterface::class, ArticleRepository::class);
    }
}
