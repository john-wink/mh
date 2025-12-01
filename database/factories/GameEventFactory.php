<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EventType;
use App\Models\Game;
use App\Models\GameEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameEvent>
 */
final class GameEventFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_id' => Game::factory(),
            'created_by_user_id' => User::factory(),
            'type' => fake()->randomElement(EventType::cases()),
            'title' => fake()->sentence(),
            'description' => fake()->optional()->paragraph(),
            'occurred_at' => now(),
            'priority' => fake()->numberBetween(0, 10),
            'data' => fake()->optional()->passthrough([
                'location' => [
                    'lat' => fake()->latitude(),
                    'lng' => fake()->longitude(),
                ],
                'details' => fake()->sentence(),
            ]),
            'is_processed' => false,
            'processed_at' => null,
        ];
    }

    public function forGame(int $gameId): self
    {
        return $this->state(fn (array $attributes): array => [
            'game_id' => $gameId,
        ]);
    }

    public function createdByUser(int $userId): self
    {
        return $this->state(fn (array $attributes): array => [
            'created_by_user_id' => $userId,
        ]);
    }

    public function manual(): self
    {
        return $this->state(fn (array $attributes): array => [
            'type' => EventType::Manual,
        ]);
    }

    public function automatic(): self
    {
        return $this->state(fn (array $attributes): array => [
            'type' => EventType::Automatic,
            'created_by_user_id' => null,
        ]);
    }

    public function system(): self
    {
        return $this->state(fn (array $attributes): array => [
            'type' => EventType::System,
            'created_by_user_id' => null,
        ]);
    }

    public function proximityAlert(): self
    {
        return $this->state(fn (array $attributes): array => [
            'type' => EventType::ProximityAlert,
            'created_by_user_id' => null,
            'data' => [
                'distance_meters' => fake()->numberBetween(10, 100),
                'target_id' => fake()->randomNumber(),
                'location' => [
                    'lat' => fake()->latitude(),
                    'lng' => fake()->longitude(),
                ],
            ],
        ]);
    }

    public function zoneEnter(): self
    {
        return $this->state(fn (array $attributes): array => [
            'type' => EventType::ZoneEnter,
            'created_by_user_id' => null,
            'data' => [
                'zone_id' => fake()->randomNumber(),
                'zone_name' => fake()->words(2, true),
                'player_id' => fake()->randomNumber(),
            ],
        ]);
    }

    public function zoneExit(): self
    {
        return $this->state(fn (array $attributes): array => [
            'type' => EventType::ZoneExit,
            'created_by_user_id' => null,
            'data' => [
                'zone_id' => fake()->randomNumber(),
                'zone_name' => fake()->words(2, true),
                'player_id' => fake()->randomNumber(),
            ],
        ]);
    }

    public function processed(): self
    {
        return $this->state(fn (array $attributes): array => [
            'is_processed' => true,
            'processed_at' => now(),
        ]);
    }

    public function highPriority(): self
    {
        return $this->state(fn (array $attributes): array => [
            'priority' => fake()->numberBetween(80, 100),
        ]);
    }

    public function lowPriority(): self
    {
        return $this->state(fn (array $attributes): array => [
            'priority' => fake()->numberBetween(0, 20),
        ]);
    }

    public function withData(array $data): self
    {
        return $this->state(fn (array $attributes): array => [
            'data' => $data,
        ]);
    }
}
