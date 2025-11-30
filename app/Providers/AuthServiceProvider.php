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
        $this->app->singleton('auth.password', function ($app): TenantPasswordBrokerManager {
            return new TenantPasswordBrokerManager($app);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
