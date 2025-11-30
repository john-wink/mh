<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Auth\TenantPasswordBrokerManager;
use Illuminate\Support\ServiceProvider;

final class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register tenant-aware password broker manager
        $this->app->extend('auth.password', fn (): TenantPasswordBrokerManager => new TenantPasswordBrokerManager($this->app));
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
