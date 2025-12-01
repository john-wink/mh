<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\GamePhase;
use App\Enums\GameStatus;
use App\Models\Game;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Game>
 */
final class GameFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(3, true);

        return [
            'organization_id' => Organization::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->optional()->paragraph(),
            'status' => GameStatus::Setup,
            'current_phase' => GamePhase::Setup,
            'state_metadata' => null,
            'start_time' => null,
            'end_time' => null,
            'config' => [
                'max_players' => fake()->numberBetween(10, 100),
                'duration_minutes' => fake()->numberBetween(60, 180),
            ],
            'rules' => [
                'allow_jokers' => fake()->boolean(),
                'safe_zone_time' => fake()->numberBetween(5, 15),
            ],
        ];
    }

    public function forOrganization(int $organizationId): self
    {
        return $this->state(fn (array $attributes): array => [
            'organization_id' => $organizationId,
        ]);
    }

    public function inSetup(): self
    {
        return $this->state(fn (array $attributes): array => [
            'current_phase' => GamePhase::Setup,
            'setup_started_at' => now(),
        ]);
    }

    public function inPreGame(): self
    {
        return $this->state(fn (array $attributes): array => [
            'current_phase' => GamePhase::PreGame,
            'setup_started_at' => now()->subHours(2),
            'pre_game_started_at' => now()->subHour(),
        ]);
    }

    public function active(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => GameStatus::Active,
            'current_phase' => GamePhase::Active,
            'start_time' => now()->subHour(),
            'setup_started_at' => now()->subHours(3),
            'pre_game_started_at' => now()->subHours(2),
            'game_started_at' => now()->subHour(),
        ]);
    }

    public function archived(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => GameStatus::Archived,
            'current_phase' => GamePhase::PostGame,
            'start_time' => now()->subHours(5),
            'end_time' => now()->subHour(),
            'setup_started_at' => now()->subHours(6),
            'pre_game_started_at' => now()->subHours(5),
            'game_started_at' => now()->subHours(4),
            'game_ended_at' => now()->subHours(2),
            'post_game_started_at' => now()->subHour(),
        ]);
    }

    public function inEndgame(): self
    {
        return $this->state(fn (array $attributes): array => [
            'current_phase' => GamePhase::Endgame,
            'setup_started_at' => now()->subHours(5),
            'pre_game_started_at' => now()->subHours(4),
            'game_started_at' => now()->subHours(3),
            'game_ended_at' => now()->subHour(),
        ]);
    }

    public function inPostGame(): self
    {
        return $this->state(fn (array $attributes): array => [
            'current_phase' => GamePhase::PostGame,
            'setup_started_at' => now()->subHours(6),
            'pre_game_started_at' => now()->subHours(5),
            'game_started_at' => now()->subHours(4),
            'game_ended_at' => now()->subHours(2),
            'post_game_started_at' => now()->subHour(),
        ]);
    }

    public function withConfig(array $config): self
    {
        return $this->state(fn (array $attributes): array => [
            'config' => array_merge($attributes['config'] ?? [], $config),
        ]);
    }

    public function withRules(array $rules): self
    {
        return $this->state(fn (array $attributes): array => [
            'rules' => array_merge($attributes['rules'] ?? [], $rules),
        ]);
    }
}
