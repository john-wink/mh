<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EventType;
use App\Traits\TableNameTrait;
use App\Traits\UuidTrait;
use Carbon\CarbonInterface;
use Database\Factories\GameEventFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read int $id
 * @property-read string $uuid
 * @property-read int $game_id
 * @property-read int|null $created_by_user_id
 * @property-read EventType $type
 * @property-read string $title
 * @property-read string|null $description
 * @property-read CarbonInterface $occurred_at
 * @property-read int $priority
 * @property-read array|null $data
 * @property-read bool $is_processed
 * @property-read CarbonInterface|null $processed_at
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read CarbonInterface|null $deleted_at
 */
final class GameEvent extends Model
{
    /** @use HasFactory<GameEventFactory> */
    use HasFactory, SoftDeletes, TableNameTrait, UuidTrait;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'game_id',
        'created_by_user_id',
        'type',
        'title',
        'description',
        'occurred_at',
        'priority',
        'data',
        'is_processed',
        'processed_at',
    ];

    /**
     * Get validation rules for creating an event
     *
     * @return array<string, mixed>
     */
    public static function createRules(): array
    {
        return [
            'game_id' => ['required', 'integer', 'exists:games,id'],
            'created_by_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'type' => ['required', 'string', 'in:'.implode(',', EventType::values())],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'occurred_at' => ['required', 'date'],
            'priority' => ['integer', 'min:0', 'max:100'],
            'data' => ['nullable', 'array'],
        ];
    }

    /**
     * Get validation rules for updating an event
     *
     * @return array<string, mixed>
     */
    public static function updateRules(int $eventId): array
    {
        return [
            'game_id' => ['sometimes', 'integer', 'exists:games,id'],
            'created_by_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'type' => ['sometimes', 'string', 'in:'.implode(',', EventType::values())],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'occurred_at' => ['sometimes', 'date'],
            'priority' => ['integer', 'min:0', 'max:100'],
            'data' => ['nullable', 'array'],
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
            'created_by_user_id' => 'integer',
            'type' => EventType::class,
            'title' => 'string',
            'description' => 'string',
            'occurred_at' => 'datetime',
            'priority' => 'integer',
            'data' => 'array',
            'is_processed' => 'boolean',
            'processed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get the game that owns the event
     *
     * @return BelongsTo<Game, $this>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * Get the user who created the event
     *
     * @return BelongsTo<User, $this>
     */
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Mark event as processed
     */
    public function markAsProcessed(): bool
    {
        $this->is_processed = true;
        $this->processed_at = now();

        return $this->save();
    }

    /**
     * Mark event as unprocessed
     */
    public function markAsUnprocessed(): bool
    {
        $this->is_processed = false;
        $this->processed_at = null;

        return $this->save();
    }

    /**
     * Scope to filter events by game
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    #[Scope]
    protected function forGame($query, int $gameId): void
    {
        $query->where('game_id', $gameId);
    }

    /**
     * Scope to filter unprocessed events
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    #[Scope]
    protected function unprocessed($query): void
    {
        $query->where('is_processed', false);
    }

    /**
     * Scope to filter processed events
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    #[Scope]
    protected function processed($query): void
    {
        $query->where('is_processed', true);
    }

    /**
     * Scope to filter by event type
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    #[Scope]
    protected function ofType($query, EventType $type): void
    {
        $query->where('type', $type->value);
    }

    /**
     * Scope to order by priority (highest first)
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    #[Scope]
    protected function byPriority($query): void
    {
        $query->latest('priority');
    }

    /**
     * Scope to order by occurrence time
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    #[Scope]
    protected function chronological($query): void
    {
        $query->oldest('occurred_at');
    }
}
