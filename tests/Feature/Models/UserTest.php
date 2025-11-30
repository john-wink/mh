<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create a user', function (): void {
    $organization = Organization::factory()->create();
    $user = User::factory()->create([
        'organization_id' => $organization->id,
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    expect($user->name)->toBe('John Doe')
        ->and($user->email)->toBe('john@example.com')
        ->and($user->organization_id)->toBe($organization->id);
});

it('can soft delete a user', function (): void {
    $user = User::factory()->create();

    $user->delete();

    expect($user->trashed())->toBeTrue()
        ->and(User::query()->withTrashed()->find($user->id))->not->toBeNull();
});

it('belongs to an organization', function (): void {
    $organization = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $organization->id]);

    expect($user->organization->id)->toBe($organization->id);
});

it('has many roles through pivot table', function (): void {
    $organization = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $organization->id]);
    $roles = Role::factory()->count(3)->create([
        'organization_id' => $organization->id,
    ]);

    $user->roles()->attach($roles);

    expect($user->roles)->toHaveCount(3)
        ->and($user->roles->first()->id)->toBe($roles->first()->id);
});

it('enforces unique email per organization', function (): void {
    $organization = Organization::factory()->create();

    User::factory()->create([
        'organization_id' => $organization->id,
        'email' => 'john@example.com',
    ]);

    expect(fn () => User::factory()->create([
        'organization_id' => $organization->id,
        'email' => 'john@example.com',
    ]))->toThrow(Exception::class);
});

it('allows same email for different organizations', function (): void {
    $org1 = Organization::factory()->create();
    $org2 = Organization::factory()->create();

    $user1 = User::factory()->create([
        'organization_id' => $org1->id,
        'email' => 'john@example.com',
    ]);

    $user2 = User::factory()->create([
        'organization_id' => $org2->id,
        'email' => 'john@example.com',
    ]);

    expect($user1->email)->toBe($user2->email)
        ->and($user1->organization_id)->not->toBe($user2->organization_id);
});
