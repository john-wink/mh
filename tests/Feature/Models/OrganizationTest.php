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

it('can filter active organizations using scope', function (): void {
    Organization::factory()->count(3)->create(['is_active' => true]);
    Organization::factory()->count(2)->inactive()->create();

    $activeOrganizations = Organization::query()->active()->get();

    expect($activeOrganizations)->toHaveCount(3);
});

it('can filter inactive organizations using scope', function (): void {
    Organization::factory()->count(3)->create(['is_active' => true]);
    Organization::factory()->count(2)->inactive()->create();

    $inactiveOrganizations = Organization::query()->inactive()->get();

    expect($inactiveOrganizations)->toHaveCount(2);
});

it('can search organizations by name', function (): void {
    Organization::factory()->create(['name' => 'Acme Corporation']);
    Organization::factory()->create(['name' => 'Beta Industries']);
    Organization::factory()->create(['name' => 'Acme Solutions']);

    $results = Organization::query()->search('Acme')->get();

    expect($results)->toHaveCount(2);
});

it('can search organizations by description', function (): void {
    Organization::factory()->create(['description' => 'Leading provider of solutions']);
    Organization::factory()->create(['description' => 'Tech solutions company']);
    Organization::factory()->create(['description' => 'Manufacturing company']);

    $results = Organization::query()->search('solutions')->get();

    expect($results)->toHaveCount(2);
});

it('has validation rules for creating', function (): void {
    $rules = Organization::createRules();

    expect($rules)->toHaveKey('name')
        ->and($rules)->toHaveKey('slug')
        ->and($rules)->toHaveKey('description')
        ->and($rules)->toHaveKey('is_active')
        ->and($rules['name'])->toContain('required')
        ->and($rules['slug'])->toContain('unique:organizations,slug');
});

it('has validation rules for updating', function (): void {
    $organization = Organization::factory()->create();
    $rules = Organization::updateRules($organization->id);

    expect($rules)->toHaveKey('name')
        ->and($rules)->toHaveKey('slug')
        ->and($rules['name'])->toContain('sometimes')
        ->and(implode(',', $rules['slug']))->toContain('unique:organizations,slug,'.$organization->id);
});
