<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GamePhase;
use App\Traits\BelongsToTenant;
use App\Traits\TableNameTrait;
use App\Traits\UuidTrait;
use Carbon\CarbonInterface;
use Database\Factories\GameFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use InvalidArgumentException;

/**
 * @property-read int $id
 * @property-read string $uuid
 * @property-read int $organization_id
 * @property-read string $name
 * @property-read string|null $description
 * @property-read GamePhase $current_phase
 * @property-read array|null $state_metadata
 * @property-read CarbonInterface|null $setup_started_at
 * @property-read CarbonInterface|null $pre_game_started_at
 * @property-read CarbonInterface|null $game_started_at
 * @property-read CarbonInterface|null $game_ended_at
 * @property-read CarbonInterface|null $post_game_started_at
 * @property-read array|null $config
 * @property-read array|null $rules
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read CarbonInterface|null $deleted_at
 */
final class Game extends Model
{
    /** @use HasFactory<GameFactory> */
    use BelongsToTenant, HasFactory, SoftDeletes, TableNameTrait, UuidTrait;

    /**
     * Get validation rules for creating a game
     *
     * @return array<string, mixed>
     */
    public static function createRules(): array
    {
        return [
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'current_phase' => ['nullable', 'string', 'in:'.implode(',', GamePhase::values())],
            'state_metadata' => ['nullable', 'array'],
            'config' => ['nullable', 'array'],
            'rules' => ['nullable', 'array'],
        ];
    }

    /**
     * Get validation rules for updating a game
     *
     * @return array<string, mixed>
     */
    public static function updateRules(int $gameId): array
    {
        return [
            'organization_id' => ['sometimes', 'integer', 'exists:organizations,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'current_phase' => ['sometimes', 'string', 'in:'.implode(',', GamePhase::values())],
            'state_metadata' => ['nullable', 'array'],
            'config' => ['nullable', 'array'],
            'rules' => ['nullable', 'array'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'integer',
            'organization_id' => 'integer',
            'name' => 'string',
            'description' => 'string',
            'current_phase' => GamePhase::class,
            'state_metadata' => 'array',
            'setup_started_at' => 'datetime',
            'pre_game_started_at' => 'datetime',
            'game_started_at' => 'datetime',
            'game_ended_at' => 'datetime',
            'post_game_started_at' => 'datetime',
            'config' => 'array',
            'rules' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get the state transitions for this game
     *
     * @return HasMany<GameStateTransition, $this>
     */
    public function stateTransitions(): HasMany
    {
        return $this->hasMany(GameStateTransition::class);
    }

    /**
     * Get the events for this game
     *
     * @return HasMany<GameEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(GameEvent::class);
    }

    /**
     * Transition to a new phase
     */
    public function transitionToPhase(GamePhase $newPhase, ?User $user = null, ?string $reason = null, ?array $metadata = null): GameStateTransition
    {
        // Validate transition
        throw_unless($this->current_phase->canTransitionTo($newPhase), new InvalidArgumentException(
            "Cannot transition from {$this->current_phase->value} to {$newPhase->value}"
        ));

        $oldPhase = $this->current_phase;

        // Update game phase
        $this->current_phase = $newPhase;

        // Update appropriate timestamp
        $this->updatePhaseTimestamp($newPhase);

        $this->save();

        // Create transition record
        return $this->stateTransitions()->create([
            'user_id' => $user?->id,
            'from_phase' => $oldPhase->value,
            'to_phase' => $newPhase->value,
            'reason' => $reason,
            'metadata' => $metadata,
            'transitioned_at' => now(),
        ]);
    }

    /**
     * Check if game is in a playable phase
     */
    public function isPlayable(): bool
    {
        return $this->current_phase->isPlayable();
    }

    /**
     * Check if game is in setup phase
     */
    public function isInSetup(): bool
    {
        return $this->current_phase === GamePhase::Setup;
    }

    /**
     * Check if game is active
     */
    public function isActive(): bool
    {
        return $this->current_phase === GamePhase::Active;
    }

    /**
     * Check if game has ended
     */
    public function hasEnded(): bool
    {
        return in_array($this->current_phase, [GamePhase::Endgame, GamePhase::PostGame], true);
    }

    /**
     * Update the appropriate timestamp when transitioning phases
     */
    private function updatePhaseTimestamp(GamePhase $phase): void
    {
        match ($phase) {
            GamePhase::Setup => $this->setup_started_at = now(),
            GamePhase::PreGame => $this->pre_game_started_at = now(),
            GamePhase::Active => $this->game_started_at = now(),
            GamePhase::Endgame => $this->game_ended_at = now(),
            GamePhase::PostGame => $this->post_game_started_at = now(),
        };
    }
}
