<?php

declare(strict_types=1);

namespace App\Filament\Management\Resources\Games\Pages;

use App\Filament\Management\Resources\Games\GameResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListGames extends ListRecords
{
    protected static string $resource = GameResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
