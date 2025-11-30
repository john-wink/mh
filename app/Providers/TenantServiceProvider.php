<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\TenantManager;
use Illuminate\Support\ServiceProvider;

final class TenantServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register TenantManager as singleton
        $this->app->singleton(TenantManager::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
