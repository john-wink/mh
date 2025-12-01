<?php

declare(strict_types=1);

namespace App\Enums;

enum GamePhase: string
{
    case Setup = 'setup';
    case PreGame = 'pre_game';
    case Active = 'active';
    case Endgame = 'endgame';
    case PostGame = 'post_game';

    /**
     * Get all possible phase values
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get the label for the phase
     */
    public function label(): string
    {
        return match ($this) {
            self::Setup => 'Setup',
            self::PreGame => 'Pre-Game',
            self::Active => 'Active',
            self::Endgame => 'Endgame',
            self::PostGame => 'Post-Game',
        };
    }

    /**
     * Check if this is a playable phase
     */
    public function isPlayable(): bool
    {
        return in_array($this, [self::Active, self::Endgame], true);
    }

    /**
     * Get valid next phases from current phase
     *
     * @return array<GamePhase>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Setup => [self::PreGame],
            self::PreGame => [self::Active, self::Setup],
            self::Active => [self::Endgame, self::PostGame],
            self::Endgame => [self::PostGame],
            self::PostGame => [],
        };
    }

    /**
     * Check if transition to target phase is valid
     */
    public function canTransitionTo(GamePhase $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
