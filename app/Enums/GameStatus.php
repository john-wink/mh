<?php

declare(strict_types=1);

namespace App\Enums;

enum GameStatus: string
{
    case Setup = 'setup';
    case PreGame = 'pre_game';
    case Active = 'active';
    case FinalSprint = 'final_sprint';
    case Endgame = 'endgame';
    case PostGame = 'post_game';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Setup => 'Setup',
            self::PreGame => 'Pre-Game',
            self::Active => 'Active',
            self::FinalSprint => 'Final Sprint',
            self::Endgame => 'Endgame',
            self::PostGame => 'Post-Game',
            self::Archived => 'Archived',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Setup => 'gray',
            self::PreGame => 'info',
            self::Active => 'success',
            self::FinalSprint => 'warning',
            self::Endgame => 'danger',
            self::PostGame => 'primary',
            self::Archived => 'secondary',
        };
    }
}
