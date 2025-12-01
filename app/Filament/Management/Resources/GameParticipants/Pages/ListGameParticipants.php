<?php

declare(strict_types=1);

namespace App\Filament\Management\Resources\GameParticipants\Pages;

use App\Filament\Management\Resources\GameParticipants\GameParticipantResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListGameParticipants extends ListRecords
{
    protected static string $resource = GameParticipantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
