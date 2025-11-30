<?php

namespace App\Filament\Widgets;

use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Organizations', Organization::count())
                ->description('Active organizations in the system')
                ->descriptionIcon('heroicon-o-building-office')
                ->color('success'),
            Stat::make('Total Users', User::count())
                ->description('Registered users across all organizations')
                ->descriptionIcon('heroicon-o-users')
                ->color('primary'),
            Stat::make('Total Roles', Role::count())
                ->description('Defined roles in the system')
                ->descriptionIcon('heroicon-o-shield-check')
                ->color('warning'),
        ];
    }
}
