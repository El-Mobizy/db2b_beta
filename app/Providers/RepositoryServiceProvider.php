<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Interfaces\RightRepositoryInterface;
use App\Repositories\RightRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(RightRepositoryInterface::class,RightRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
