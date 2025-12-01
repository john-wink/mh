<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GamePhase;
use App\Traits\TableNameTrait;
use App\Traits\UuidTrait;
use Carbon\CarbonInterface;
use Database\Factories\GameStateTransitionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read string $uuid
 * @property-read int $game_id
 * @property-read int|null $user_id
 * @property-read string $from_phase
 * @property-read string $to_phase
 * @property-read string|null $reason
 * @property-read array|null $metadata
 * @property-read bool $is_valid
 * @property-read string|null $validation_notes
 * @property-read CarbonInterface $transitioned_at
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class GameStateTransition extends Model
{
    /** @use HasFactory<GameStateTransitionFactory> */
    use HasFactory, TableNameTrait, UuidTrait;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'game_id',
        'user_id',
        'from_phase',
        'to_phase',
        'reason',
        'metadata',
        'is_valid',
        'validation_notes',
        'transitioned_at',
    ];

    /**
     * Get validation rules for creating a transition
     *
     * @return array<string, mixed>
     */
    public static function createRules(): array
    {
        return [
            'game_id' => ['required', 'integer', 'exists:games,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'from_phase' => ['required', 'string', 'in:'.implode(',', GamePhase::values())],
            'to_phase' => ['required', 'string', 'in:'.implode(',', GamePhase::values())],
            'reason' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
            'is_valid' => ['boolean'],
            'validation_notes' => ['nullable', 'string'],
            'transitioned_at' => ['required', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'integer',
            'game_id' => 'integer',
            'user_id' => 'integer',
            'from_phase' => 'string',
            'to_phase' => 'string',
            'reason' => 'string',
            'metadata' => 'array',
            'is_valid' => 'boolean',
            'validation_notes' => 'string',
            'transitioned_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the game that owns the transition
     *
     * @return BelongsTo<Game, $this>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * Get the user who initiated the transition
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the from phase as enum
     */
    public function getFromPhaseEnum(): GamePhase
    {
        return GamePhase::from($this->from_phase);
    }

    /**
     * Get the to phase as enum
     */
    public function getToPhaseEnum(): GamePhase
    {
        return GamePhase::from($this->to_phase);
    }

    /**
     * Mark transition as valid
     */
    public function markAsValid(?string $notes = null): bool
    {
        $this->is_valid = true;
        $this->validation_notes = $notes;

        return $this->save();
    }

    /**
     * Mark transition as invalid
     */
    public function markAsInvalid(string $notes): bool
    {
        $this->is_valid = false;
        $this->validation_notes = $notes;

        return $this->save();
    }
}
