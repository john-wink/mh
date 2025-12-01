<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\GamePhase;
use App\Models\Game;
use App\Models\GameStateTransition;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameStateTransition>
 */
final class GameStateTransitionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_id' => Game::factory(),
            'user_id' => User::factory(),
            'from_phase' => GamePhase::Setup->value,
            'to_phase' => GamePhase::PreGame->value,
            'reason' => fake()->optional()->sentence(),
            'metadata' => fake()->optional()->passthrough([
                'notes' => fake()->sentence(),
                'triggered_by' => fake()->randomElement(['manual', 'automatic', 'scheduled']),
            ]),
            'is_valid' => true,
            'validation_notes' => null,
            'transitioned_at' => now(),
        ];
    }

    public function forGame(int $gameId): self
    {
        return $this->state(fn (array $attributes): array => [
            'game_id' => $gameId,
        ]);
    }

    public function byUser(int $userId): self
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $userId,
        ]);
    }

    public function fromSetupToPreGame(): self
    {
        return $this->state(fn (array $attributes): array => [
            'from_phase' => GamePhase::Setup->value,
            'to_phase' => GamePhase::PreGame->value,
        ]);
    }

    public function fromPreGameToActive(): self
    {
        return $this->state(fn (array $attributes): array => [
            'from_phase' => GamePhase::PreGame->value,
            'to_phase' => GamePhase::Active->value,
        ]);
    }

    public function fromActiveToEndgame(): self
    {
        return $this->state(fn (array $attributes): array => [
            'from_phase' => GamePhase::Active->value,
            'to_phase' => GamePhase::Endgame->value,
        ]);
    }

    public function fromEndgameToPostGame(): self
    {
        return $this->state(fn (array $attributes): array => [
            'from_phase' => GamePhase::Endgame->value,
            'to_phase' => GamePhase::PostGame->value,
        ]);
    }

    public function invalid(): self
    {
        return $this->state(fn (array $attributes): array => [
            'is_valid' => false,
            'validation_notes' => fake()->sentence(),
        ]);
    }

    public function withReason(string $reason): self
    {
        return $this->state(fn (array $attributes): array => [
            'reason' => $reason,
        ]);
    }

    public function withMetadata(array $metadata): self
    {
        return $this->state(fn (array $attributes): array => [
            'metadata' => $metadata,
        ]);
    }
}
