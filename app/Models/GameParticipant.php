<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ParticipantRole;
use App\Traits\TableNameTrait;
use App\Traits\UuidTrait;
use Carbon\CarbonInterface;
use Database\Factories\GameParticipantFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read int $id
 * @property-read int $game_id
 * @property-read int $user_id
 * @property-read ParticipantRole $role
 * @property-read int|null $participant_number
 * @property-read array|null $settings
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read CarbonInterface|null $deleted_at
 */
final class GameParticipant extends Model
{
    /** @use HasFactory<GameParticipantFactory> */
    use HasFactory, SoftDeletes, TableNameTrait, UuidTrait;

    /**
     * Get validation rules for creating a game participant
     *
     * @return array<string, mixed>
     */
    public static function createRules(): array
    {
        return [
            'game_id' => ['required', 'integer', 'exists:games,id'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'role' => ['required', 'string', 'in:game_master,assistant,runner,hunter,hunter_coordinator,security,spectator,director'],
            'participant_number' => ['nullable', 'integer', 'min:1'],
            'settings' => ['nullable', 'array'],
        ];
    }

    /**
     * Get validation rules for updating a game participant
     *
     * @return array<string, mixed>
     */
    public static function updateRules(int $participantId): array
    {
        return [
            'game_id' => ['sometimes', 'integer', 'exists:games,id'],
            'user_id' => ['sometimes', 'integer', 'exists:users,id'],
            'role' => ['sometimes', 'string', 'in:game_master,assistant,runner,hunter,hunter_coordinator,security,spectator,director'],
            'participant_number' => ['nullable', 'integer', 'min:1'],
            'settings' => ['nullable', 'array'],
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
            'role' => ParticipantRole::class,
            'participant_number' => 'integer',
            'settings' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Game, $this>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to filter participants by game
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function forGame($query, int $gameId): void
    {
        $query->where('game_id', $gameId);
    }

    /**
     * Scope to filter participants by role
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function byRole($query, ParticipantRole $role): void
    {
        $query->where('role', $role);
    }

    /**
     * Scope to get runners
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function runners($query): void
    {
        $query->where('role', ParticipantRole::Runner);
    }

    /**
     * Scope to get hunters
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function hunters($query): void
    {
        $query->where('role', ParticipantRole::Hunter);
    }

    /**
     * Scope to get game masters
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function gameMasters($query): void
    {
        $query->where('role', ParticipantRole::GameMaster);
    }
}
