<?php

declare(strict_types=1);

namespace App\Enums;

enum ParticipantRole: string
{
    case GameMaster = 'game_master';
    case Assistant = 'assistant';
    case Runner = 'runner';
    case Hunter = 'hunter';
    case HunterCoordinator = 'hunter_coordinator';
    case Security = 'security';
    case Spectator = 'spectator';
    case Director = 'director';

    public function label(): string
    {
        return match ($this) {
            self::GameMaster => 'Game Master',
            self::Assistant => 'Assistant',
            self::Runner => 'Runner',
            self::Hunter => 'Hunter',
            self::HunterCoordinator => 'Hunter Coordinator',
            self::Security => 'Security',
            self::Spectator => 'Spectator',
            self::Director => 'Director',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::GameMaster => 'danger',
            self::Assistant => 'info',
            self::Runner => 'success',
            self::Hunter => 'warning',
            self::HunterCoordinator => 'primary',
            self::Security => 'secondary',
            self::Spectator => 'gray',
            self::Director => 'purple',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::GameMaster => 'Manages and oversees the entire game',
            self::Assistant => 'Assists the Game Master with game operations',
            self::Runner => 'Participates in the game as a runner',
            self::Hunter => 'Participates in the game as a hunter',
            self::HunterCoordinator => 'Coordinates hunter team activities',
            self::Security => 'Provides security and safety oversight',
            self::Spectator => 'Observes the game without active participation',
            self::Director => 'High-level oversight and strategic direction',
        };
    }
}
