<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create an organization', function (): void {
    $organization = Organization::factory()->create([
        'name' => 'Acme Corp',
        'slug' => 'acme-corp',
        'description' => 'A test organization',
        'is_active' => true,
    ]);

    expect($organization->name)->toBe('Acme Corp')
        ->and($organization->slug)->toBe('acme-corp')
        ->and($organization->description)->toBe('A test organization')
        ->and($organization->is_active)->toBeTrue();
});

it('can soft delete an organization', function (): void {
    $organization = Organization::factory()->create();

    $organization->delete();

    expect($organization->trashed())->toBeTrue()
        ->and(Organization::query()->withTrashed()->find($organization->id))->not->toBeNull();
});

it('has many users', function (): void {
    $organization = Organization::factory()->create();
    $users = User::factory()->count(3)->create([
        'organization_id' => $organization->id,
    ]);

    expect($organization->users)->toHaveCount(3)
        ->and($organization->users->first()->id)->toBe($users->first()->id);
});

it('has many roles', function (): void {
    $organization = Organization::factory()->create();
    $roles = Role::factory()->count(3)->create([
        'organization_id' => $organization->id,
    ]);

    expect($organization->roles)->toHaveCount(3)
        ->and($organization->roles->first()->id)->toBe($roles->first()->id);
});

it('cascades delete to users and roles', function (): void {
    $organization = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $organization->id]);
    $role = Role::factory()->create(['organization_id' => $organization->id]);

    $organization->forceDelete();

    expect(User::query()->find($user->id))->toBeNull()
        ->and(Role::query()->find($role->id))->toBeNull();
});

it('can be inactive', function (): void {
    $organization = Organization::factory()->inactive()->create();

    expect($organization->is_active)->toBeFalse();
});
