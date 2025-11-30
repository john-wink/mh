<?php

declare(strict_types=1);

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create a permission', function (): void {
    $permission = Permission::factory()->create([
        'name' => 'Create Users',
        'slug' => 'create-users',
        'description' => 'Can create new users',
    ]);

    expect($permission->name)->toBe('Create Users')
        ->and($permission->slug)->toBe('create-users')
        ->and($permission->description)->toBe('Can create new users');
});

it('can soft delete a permission', function (): void {
    $permission = Permission::factory()->create();

    $permission->delete();

    expect($permission->trashed())->toBeTrue()
        ->and(Permission::query()->withTrashed()->find($permission->id))->not->toBeNull();
});

it('has many roles through pivot table', function (): void {
    $permission = Permission::factory()->create();
    $roles = Role::factory()->count(3)->create();

    $permission->roles()->attach($roles);

    expect($permission->roles)->toHaveCount(3)
        ->and($permission->roles->first()->id)->toBe($roles->first()->id);
});

it('enforces unique slug globally', function (): void {
    Permission::factory()->create(['slug' => 'create-users']);

    expect(fn () => Permission::factory()->create([
        'slug' => 'create-users',
    ]))->toThrow(Exception::class);
});

it('can search permissions by name', function (): void {
    Permission::factory()->create(['name' => 'Create Users']);
    Permission::factory()->create(['name' => 'Edit Posts']);
    Permission::factory()->create(['name' => 'Delete Users']);

    $results = Permission::query()->search('Users')->get();

    expect($results)->toHaveCount(2);
});

it('can search permissions by description', function (): void {
    Permission::factory()->create(['name' => 'View Users', 'slug' => 'view-users-1', 'description' => 'Allows user management']);
    Permission::factory()->create(['name' => 'Full Access', 'slug' => 'full-access', 'description' => 'Grants full access']);
    Permission::factory()->create(['name' => 'Read Users', 'slug' => 'read-users', 'description' => 'Read-only user access']);

    $results = Permission::query()->search('user')->get();

    expect($results)->toHaveCount(2);
});

it('has validation rules for creating', function (): void {
    $rules = Permission::createRules();

    expect($rules)->toHaveKey('name')
        ->and($rules)->toHaveKey('slug')
        ->and($rules)->toHaveKey('description')
        ->and($rules['name'])->toContain('required')
        ->and($rules['slug'])->toContain('unique:permissions,slug');
});

it('has validation rules for updating', function (): void {
    $permission = Permission::factory()->create();
    $rules = Permission::updateRules($permission->id);

    expect($rules)->toHaveKey('name')
        ->and($rules)->toHaveKey('slug')
        ->and($rules['name'])->toContain('sometimes')
        ->and(implode(',', $rules['slug']))->toContain('unique:permissions,slug,'.$permission->id);
});
