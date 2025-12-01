<?php

declare(strict_types=1);

namespace App\Filament\Management\Resources\Games\Schemas;

use App\Enums\GameStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

final class GameForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic Information')
                    ->schema([
                        Select::make('organization_id')
                            ->relationship('organization', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->columnSpan(1),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state)))
                            ->columnSpan(1),
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->rules(['regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'])
                            ->helperText('URL-friendly identifier (lowercase, numbers, and hyphens only)')
                            ->columnSpan(1),
                        Select::make('status')
                            ->options(
                                collect(GameStatus::cases())
                                    ->mapWithKeys(fn (GameStatus $status): array => [$status->value => $status->label()])
                                    ->toArray()
                            )
                            ->default(GameStatus::Setup->value)
                            ->required()
                            ->columnSpan(1),
                    ])->columns(2),

                Section::make('Schedule')
                    ->schema([
                        DateTimePicker::make('start_time')
                            ->label('Start Time')
                            ->native(false)
                            ->displayFormat('Y-m-d H:i')
                            ->seconds(false)
                            ->columnSpan(1),
                        DateTimePicker::make('end_time')
                            ->label('End Time')
                            ->native(false)
                            ->displayFormat('Y-m-d H:i')
                            ->seconds(false)
                            ->after('start_time')
                            ->columnSpan(1),
                    ])->columns(2),

                Section::make('Description')
                    ->schema([
                        Textarea::make('description')
                            ->maxLength(5000)
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),

                Section::make('Game Configuration')
                    ->schema([
                        KeyValue::make('config')
                            ->label('Game Configuration')
                            ->keyLabel('Config Key')
                            ->valueLabel('Value')
                            ->reorderable()
                            ->helperText('Configure game settings (e.g., max_players, duration_minutes)')
                            ->columnSpanFull(),
                        KeyValue::make('rules')
                            ->label('Game Rules')
                            ->keyLabel('Rule Name')
                            ->valueLabel('Value')
                            ->reorderable()
                            ->helperText('Define game rules (e.g., allow_jokers, safe_zone_time)')
                            ->columnSpanFull(),
                    ])->collapsible(),
            ]);
    }
}
