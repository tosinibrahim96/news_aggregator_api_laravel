<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Repositories\ArticleRepositoryInterface;
use App\Contracts\Repositories\AuthRepositoryInterface;
use App\Contracts\Repositories\PreferenceRepositoryInterface;
use App\Repositories\ArticleRepository;
use App\Repositories\AuthRepository;
use App\Repositories\PreferenceRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register the repository bindings.
     */
    public function register(): void
    {
        $this->app->bind(ArticleRepositoryInterface::class, ArticleRepository::class);
        $this->app->bind(AuthRepositoryInterface::class, AuthRepository::class);
        $this->app->bind(PreferenceRepositoryInterface::class, PreferenceRepository::class);
    }
}
