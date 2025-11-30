<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class StatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Organizations', Organization::query()->count())
                ->description('Active organizations in the system')
                ->descriptionIcon('heroicon-o-building-office')
                ->color('success'),
            Stat::make('Total Users', User::query()->count())
                ->description('Registered users across all organizations')
                ->descriptionIcon('heroicon-o-users')
                ->color('primary'),
            Stat::make('Total Roles', Role::query()->count())
                ->description('Defined roles in the system')
                ->descriptionIcon('heroicon-o-shield-check')
                ->color('warning'),
        ];
    }
}
