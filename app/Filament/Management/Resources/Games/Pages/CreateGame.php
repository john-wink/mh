<?php

declare(strict_types=1);

namespace App\Filament\Management\Resources\Games\Pages;

use App\Filament\Management\Resources\Games\GameResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateGame extends CreateRecord
{
    protected static string $resource = GameResource::class;
}
