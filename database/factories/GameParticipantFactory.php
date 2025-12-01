<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ParticipantRole;
use App\Models\Game;
use App\Models\GameParticipant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameParticipant>
 */
final class GameParticipantFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_id' => Game::factory(),
            'user_id' => User::factory(),
            'role' => fake()->randomElement(ParticipantRole::cases()),
            'participant_number' => fake()->numberBetween(1, 100),
            'settings' => [
                'preferred_communication' => fake()->randomElement(['email', 'sms', 'app']),
                'emergency_contact' => fake()->phoneNumber(),
            ],
        ];
    }

    public function gameMaster(): self
    {
        return $this->state(fn (array $attributes): array => [
            'role' => ParticipantRole::GameMaster,
            'participant_number' => null,
        ]);
    }

    public function runner(): self
    {
        return $this->state(fn (array $attributes): array => [
            'role' => ParticipantRole::Runner,
        ]);
    }

    public function hunter(): self
    {
        return $this->state(fn (array $attributes): array => [
            'role' => ParticipantRole::Hunter,
        ]);
    }

    public function hunterCoordinator(): self
    {
        return $this->state(fn (array $attributes): array => [
            'role' => ParticipantRole::HunterCoordinator,
            'participant_number' => null,
        ]);
    }

    public function assistant(): self
    {
        return $this->state(fn (array $attributes): array => [
            'role' => ParticipantRole::Assistant,
            'participant_number' => null,
        ]);
    }

    public function security(): self
    {
        return $this->state(fn (array $attributes): array => [
            'role' => ParticipantRole::Security,
            'participant_number' => null,
        ]);
    }

    public function spectator(): self
    {
        return $this->state(fn (array $attributes): array => [
            'role' => ParticipantRole::Spectator,
        ]);
    }

    public function director(): self
    {
        return $this->state(fn (array $attributes): array => [
            'role' => ParticipantRole::Director,
            'participant_number' => null,
        ]);
    }
}
