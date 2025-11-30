<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Role>
 */
final class RoleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Super Admin',
            'Organization Admin',
            'Game Master',
            'Game Master Assistant',
            'Runner',
            'Hunter',
            'Hunter Coordinator',
            'Security',
            'Spectator',
            'Director',
            'Manager',
            'Editor',
            'Viewer',
        ]);

        return [
            'organization_id' => Organization::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 10000),
            'description' => fake()->optional()->sentence(),
        ];
    }

    public function forOrganization(int $organizationId): static
    {
        return $this->state(fn (array $attributes): array => [
            'organization_id' => $organizationId,
        ]);
    }

    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'description' => 'Platform-wide administrator with full access',
        ]);
    }

    public function organizationAdmin(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'Organization Admin',
            'slug' => 'organization-admin',
            'description' => 'Manages organization settings and users',
        ]);
    }

    public function gameMaster(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'Game Master',
            'slug' => 'game-master',
            'description' => 'Full control over assigned games',
        ]);
    }

    public function runner(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'Runner',
            'slug' => 'runner',
            'description' => 'Player trying to evade hunters',
        ]);
    }

    public function hunter(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'Hunter',
            'slug' => 'hunter',
            'description' => 'Player trying to catch runners',
        ]);
    }
}
