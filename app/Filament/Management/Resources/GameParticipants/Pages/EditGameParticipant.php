<?php

declare(strict_types=1);

namespace App\Filament\Management\Resources\GameParticipants\Pages;

use App\Filament\Management\Resources\GameParticipants\GameParticipantResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

final class EditGameParticipant extends EditRecord
{
    protected static string $resource = GameParticipantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
