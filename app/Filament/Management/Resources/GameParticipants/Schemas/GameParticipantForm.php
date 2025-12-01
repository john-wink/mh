<?php

declare(strict_types=1);

namespace App\Filament\Management\Resources\GameParticipants\Schemas;

use App\Enums\ParticipantRole;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class GameParticipantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Participant Information')
                    ->schema([
                        Select::make('game_id')
                            ->relationship('game', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->columnSpan(1),
                        Select::make('user_id')
                            ->relationship('user', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->columnSpan(1),
                        Select::make('role')
                            ->options(
                                collect(ParticipantRole::cases())
                                    ->mapWithKeys(fn (ParticipantRole $role): array => [$role->value => $role->label()])
                                    ->toArray()
                            )
                            ->required()
                            ->helperText(fn (?string $state) => $state ? ParticipantRole::from($state)->description() : null)
                            ->columnSpan(1),
                        TextInput::make('participant_number')
                            ->label('Participant Number')
                            ->numeric()
                            ->minValue(1)
                            ->helperText('Optional identifier for runners/hunters (e.g., Runner #7)')
                            ->columnSpan(1),
                    ])->columns(2),

                Section::make('Participant Settings')
                    ->schema([
                        KeyValue::make('settings')
                            ->label('Custom Settings')
                            ->keyLabel('Setting Name')
                            ->valueLabel('Value')
                            ->reorderable()
                            ->helperText('Additional participant-specific settings')
                            ->columnSpanFull(),
                    ])->collapsible(),
            ]);
    }
}
