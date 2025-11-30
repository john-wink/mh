<?php

declare(strict_types=1);

use App\Http\Middleware\TenantResolver;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Add tenant resolver middleware to web group
        $middleware->web(append: [
            TenantResolver::class,
        ]);

        // Register middleware aliases
        $middleware->alias([
            'role' => App\Http\Middleware\CheckRole::class,
            'permission' => App\Http\Middleware\CheckPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

return $app;
