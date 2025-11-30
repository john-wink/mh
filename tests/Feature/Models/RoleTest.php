<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create a role', function (): void {
    $organization = Organization::factory()->create();
    $role = Role::factory()->create([
        'organization_id' => $organization->id,
        'name' => 'Admin',
        'slug' => 'admin',
        'description' => 'Administrator role',
    ]);

    expect($role->name)->toBe('Admin')
        ->and($role->slug)->toBe('admin')
        ->and($role->description)->toBe('Administrator role')
        ->and($role->organization_id)->toBe($organization->id);
});

it('can soft delete a role', function (): void {
    $role = Role::factory()->create();

    $role->delete();

    expect($role->trashed())->toBeTrue()
        ->and(Role::query()->withTrashed()->find($role->id))->not->toBeNull();
});

it('belongs to an organization', function (): void {
    $organization = Organization::factory()->create();
    $role = Role::factory()->create(['organization_id' => $organization->id]);

    expect($role->organization->id)->toBe($organization->id);
});

it('has many users through pivot table', function (): void {
    $role = Role::factory()->create();
    $users = User::factory()->count(3)->create([
        'organization_id' => $role->organization_id,
    ]);

    $role->users()->attach($users);

    expect($role->users)->toHaveCount(3)
        ->and($role->users->first()->id)->toBe($users->first()->id);
});

it('has many permissions through pivot table', function (): void {
    $role = Role::factory()->create();
    $permissions = Permission::factory()->count(3)->create();

    $role->permissions()->attach($permissions);

    expect($role->permissions)->toHaveCount(3)
        ->and($role->permissions->first()->id)->toBe($permissions->first()->id);
});

it('enforces unique slug per organization', function (): void {
    $organization = Organization::factory()->create();

    Role::factory()->create([
        'organization_id' => $organization->id,
        'slug' => 'admin',
    ]);

    expect(fn () => Role::factory()->create([
        'organization_id' => $organization->id,
        'slug' => 'admin',
    ]))->toThrow(Exception::class);
});

it('allows same slug for different organizations', function (): void {
    $org1 = Organization::factory()->create();
    $org2 = Organization::factory()->create();

    $role1 = Role::factory()->create([
        'organization_id' => $org1->id,
        'slug' => 'admin',
    ]);

    $role2 = Role::factory()->create([
        'organization_id' => $org2->id,
        'slug' => 'admin',
    ]);

    expect($role1->slug)->toBe($role2->slug)
        ->and($role1->organization_id)->not->toBe($role2->organization_id);
});
