<?php

declare(strict_types=1);

namespace App\Filament\Management\Resources\GameParticipants\Pages;

use App\Filament\Management\Resources\GameParticipants\GameParticipantResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateGameParticipant extends CreateRecord
{
    protected static string $resource = GameParticipantResource::class;
}
