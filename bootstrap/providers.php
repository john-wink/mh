<?php

declare(strict_types=1);

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\AuthServiceProvider::class,
    App\Providers\Filament\HunterPanelProvider::class,
    App\Providers\Filament\ManagementPanelProvider::class,
    App\Providers\Filament\PlayerPanelProvider::class,
    App\Providers\Filament\RunnerPanelProvider::class,
    App\Providers\Filament\SecurityPanelProvider::class,
    App\Providers\TenantServiceProvider::class,
];
