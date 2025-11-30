<?php

declare(strict_types=1);

namespace App\Filament\Management\Resources\Roles\Pages;

use App\Filament\Management\Resources\Roles\RoleResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;
}
