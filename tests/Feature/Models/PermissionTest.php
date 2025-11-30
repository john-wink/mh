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
