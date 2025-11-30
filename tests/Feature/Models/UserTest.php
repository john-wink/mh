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

it('can filter users by organization using scope', function (): void {
    $org1 = Organization::factory()->create();
    $org2 = Organization::factory()->create();

    User::factory()->count(3)->create(['organization_id' => $org1->id]);
    User::factory()->count(2)->create(['organization_id' => $org2->id]);

    $org1Users = User::query()->forOrganization($org1->id)->get();

    expect($org1Users)->toHaveCount(3);
});

it('can filter verified users using scope', function (): void {
    User::factory()->count(3)->create();
    User::factory()->count(2)->unverified()->create();

    $verifiedUsers = User::query()->verified()->get();

    expect($verifiedUsers)->toHaveCount(3);
});

it('can filter unverified users using scope', function (): void {
    User::factory()->count(3)->create();
    User::factory()->count(2)->unverified()->create();

    $unverifiedUsers = User::query()->unverified()->get();

    expect($unverifiedUsers)->toHaveCount(2);
});

it('can check if user has a specific role', function (): void {
    $organization = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $organization->id]);
    $role = Role::factory()->create([
        'organization_id' => $organization->id,
        'slug' => 'admin',
    ]);

    $user->roles()->attach($role);

    expect($user->hasRole('admin'))->toBeTrue()
        ->and($user->hasRole('editor'))->toBeFalse();
});

it('can check if user has any of the given roles', function (): void {
    $organization = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $organization->id]);
    $adminRole = Role::factory()->create([
        'organization_id' => $organization->id,
        'slug' => 'admin',
    ]);

    $user->roles()->attach($adminRole);

    expect($user->hasAnyRole(['admin', 'editor']))->toBeTrue()
        ->and($user->hasAnyRole(['editor', 'viewer']))->toBeFalse();
});

it('can check if user has all of the given roles', function (): void {
    $organization = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $organization->id]);
    $adminRole = Role::factory()->create([
        'organization_id' => $organization->id,
        'slug' => 'admin',
    ]);
    $editorRole = Role::factory()->create([
        'organization_id' => $organization->id,
        'slug' => 'editor',
    ]);

    $user->roles()->attach([$adminRole->id, $editorRole->id]);

    expect($user->hasAllRoles(['admin', 'editor']))->toBeTrue()
        ->and($user->hasAllRoles(['admin', 'editor', 'viewer']))->toBeFalse();
});

it('can check if user has a specific permission', function (): void {
    $organization = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $organization->id]);
    $role = Role::factory()->create(['organization_id' => $organization->id]);
    $permission = App\Models\Permission::factory()->create(['slug' => 'edit-posts']);

    $role->permissions()->attach($permission);
    $user->roles()->attach($role);

    expect($user->hasPermission('edit-posts'))->toBeTrue()
        ->and($user->hasPermission('delete-posts'))->toBeFalse();
});

it('can check if user has any of the given permissions', function (): void {
    $organization = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $organization->id]);
    $role = Role::factory()->create(['organization_id' => $organization->id]);
    $editPermission = App\Models\Permission::factory()->create(['slug' => 'edit-posts']);

    $role->permissions()->attach($editPermission);
    $user->roles()->attach($role);

    expect($user->hasAnyPermission(['edit-posts', 'delete-posts']))->toBeTrue()
        ->and($user->hasAnyPermission(['delete-posts', 'create-posts']))->toBeFalse();
});

it('has validation rules for creating', function (): void {
    $rules = User::createRules();

    expect($rules)->toHaveKey('organization_id')
        ->and($rules)->toHaveKey('name')
        ->and($rules)->toHaveKey('email')
        ->and($rules)->toHaveKey('password')
        ->and($rules['organization_id'])->toContain('required')
        ->and($rules['email'])->toContain('unique:users,email')
        ->and($rules['password'])->toContain('confirmed');
});

it('has validation rules for updating', function (): void {
    $user = User::factory()->create();
    $rules = User::updateRules($user->id);

    expect($rules)->toHaveKey('organization_id')
        ->and($rules)->toHaveKey('name')
        ->and($rules)->toHaveKey('email')
        ->and($rules['organization_id'])->toContain('sometimes')
        ->and(implode(',', $rules['email']))->toContain('unique:users,email,'.$user->id);
});
