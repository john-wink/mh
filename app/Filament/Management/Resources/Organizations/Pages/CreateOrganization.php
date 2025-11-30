<?php

declare(strict_types=1);

namespace App\Filament\Management\Resources\Organizations\Pages;

use App\Filament\Management\Resources\Organizations\OrganizationResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateOrganization extends CreateRecord
{
    protected static string $resource = OrganizationResource::class;
}
