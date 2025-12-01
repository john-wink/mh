<?php

declare(strict_types=1);

namespace App\Filament\Management\Resources\GameParticipants;

use App\Filament\Management\Resources\GameParticipants\Pages\CreateGameParticipant;
use App\Filament\Management\Resources\GameParticipants\Pages\EditGameParticipant;
use App\Filament\Management\Resources\GameParticipants\Pages\ListGameParticipants;
use App\Filament\Management\Resources\GameParticipants\Schemas\GameParticipantForm;
use App\Filament\Management\Resources\GameParticipants\Tables\GameParticipantsTable;
use App\Models\GameParticipant;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

final class GameParticipantResource extends Resource
{
    protected static ?string $model = GameParticipant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Game Management';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Participants';

    protected static ?string $pluralModelLabel = 'Game Participants';

    protected static ?string $modelLabel = 'Game Participant';

    public static function form(Schema $schema): Schema
    {
        return GameParticipantForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GameParticipantsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGameParticipants::route('/'),
            'create' => CreateGameParticipant::route('/create'),
            'edit' => EditGameParticipant::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
