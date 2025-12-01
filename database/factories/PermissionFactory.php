<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Permission>
 */
final class PermissionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'View Organizations',
            'Create Organizations',
            'Edit Organizations',
            'Delete Organizations',
            'View Users',
            'Create Users',
            'Edit Users',
            'Delete Users',
            'View Games',
            'Create Games',
            'Edit Games',
            'Delete Games',
            'Manage Game Settings',
            'Start Game',
            'Pause Game',
            'End Game',
            'View Participants',
            'Manage Participants',
            'View Tracking',
            'Manage Tracking',
            'View Zones',
            'Manage Zones',
            'View Events',
            'Create Events',
            'View Jokers',
            'Manage Jokers',
            'View Chat',
            'Moderate Chat',
            'View Transactions',
            'View Analytics',
            'Export Data',
            'Manage Roles',
            'Manage Permissions',
        ]);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Game management permissions
     */
    public function gameManagement(): static
    {
        $permissions = [
            ['name' => 'View Games', 'slug' => 'view-games'],
            ['name' => 'Create Games', 'slug' => 'create-games'],
            ['name' => 'Edit Games', 'slug' => 'edit-games'],
            ['name' => 'Delete Games', 'slug' => 'delete-games'],
            ['name' => 'Start Game', 'slug' => 'start-game'],
            ['name' => 'Pause Game', 'slug' => 'pause-game'],
            ['name' => 'End Game', 'slug' => 'end-game'],
        ];

        return $this->state(fn (array $attributes): array => fake()->randomElement($permissions));
    }

    /**
     * User management permissions
     */
    public function userManagement(): static
    {
        $permissions = [
            ['name' => 'View Users', 'slug' => 'view-users'],
            ['name' => 'Create Users', 'slug' => 'create-users'],
            ['name' => 'Edit Users', 'slug' => 'edit-users'],
            ['name' => 'Delete Users', 'slug' => 'delete-users'],
        ];

        return $this->state(fn (array $attributes): array => fake()->randomElement($permissions));
    }

    /**
     * Tracking permissions
     */
    public function tracking(): static
    {
        $permissions = [
            ['name' => 'View Tracking', 'slug' => 'view-tracking'],
            ['name' => 'Manage Tracking', 'slug' => 'manage-tracking'],
        ];

        return $this->state(fn (array $attributes): array => fake()->randomElement($permissions));
    }
}
