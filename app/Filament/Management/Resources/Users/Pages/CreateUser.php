<?php

declare(strict_types=1);

namespace App\Filament\Management\Resources\Users\Pages;

use App\Filament\Management\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
}
