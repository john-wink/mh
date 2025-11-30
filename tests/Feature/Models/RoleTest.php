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

it('can filter roles by organization using scope', function (): void {
    $org1 = Organization::factory()->create();
    $org2 = Organization::factory()->create();

    Role::factory()->count(3)->create(['organization_id' => $org1->id]);
    Role::factory()->count(2)->create(['organization_id' => $org2->id]);

    $org1Roles = Role::query()->forOrganization($org1->id)->get();

    expect($org1Roles)->toHaveCount(3);
});

it('can search roles by name', function (): void {
    $organization = Organization::factory()->create();
    Role::factory()->create(['organization_id' => $organization->id, 'name' => 'Admin Manager']);
    Role::factory()->create(['organization_id' => $organization->id, 'name' => 'Content Editor']);
    Role::factory()->create(['organization_id' => $organization->id, 'name' => 'System Admin']);

    $results = Role::query()->search('Admin')->get();

    expect($results)->toHaveCount(2);
});

it('can check if role has a specific permission', function (): void {
    $role = Role::factory()->create();
    $permission = Permission::factory()->create(['slug' => 'edit-posts']);

    $role->permissions()->attach($permission);

    expect($role->hasPermission('edit-posts'))->toBeTrue()
        ->and($role->hasPermission('delete-posts'))->toBeFalse();
});

it('can give a permission to a role', function (): void {
    $role = Role::factory()->create();
    $permission = Permission::factory()->create();

    $role->givePermission($permission);

    expect($role->permissions)->toHaveCount(1)
        ->and($role->hasPermission($permission->slug))->toBeTrue();
});

it('does not duplicate permissions when giving same permission twice', function (): void {
    $role = Role::factory()->create();
    $permission = Permission::factory()->create();

    $role->givePermission($permission);
    $role->givePermission($permission);

    expect($role->permissions)->toHaveCount(1);
});

it('can revoke a permission from a role', function (): void {
    $role = Role::factory()->create();
    $permission = Permission::factory()->create();

    $role->permissions()->attach($permission);
    expect($role->permissions)->toHaveCount(1);

    $role->revokePermission($permission);

    expect($role->fresh()->permissions)->toHaveCount(0);
});

it('has validation rules for creating', function (): void {
    $rules = Role::createRules();

    expect($rules)->toHaveKey('organization_id')
        ->and($rules)->toHaveKey('name')
        ->and($rules)->toHaveKey('slug')
        ->and($rules)->toHaveKey('description')
        ->and($rules['organization_id'])->toContain('required')
        ->and($rules['name'])->toContain('required');
});

it('has validation rules for updating', function (): void {
    $role = Role::factory()->create();
    $rules = Role::updateRules($role->id);

    expect($rules)->toHaveKey('organization_id')
        ->and($rules)->toHaveKey('name')
        ->and($rules)->toHaveKey('slug')
        ->and($rules['organization_id'])->toContain('sometimes')
        ->and($rules['name'])->toContain('sometimes');
});
