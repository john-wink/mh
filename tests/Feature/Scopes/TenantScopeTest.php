<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Services\TenantManager;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenantManager = app(TenantManager::class);
    $this->tenant1 = Organization::factory()->create(['slug' => 'tenant1']);
    $this->tenant2 = Organization::factory()->create(['slug' => 'tenant2']);
});

it('automatically filters users by current tenant', function (): void {
    // Create users for both tenants
    $user1 = User::factory()->create(['organization_id' => $this->tenant1->id]);
    $user2 = User::factory()->create(['organization_id' => $this->tenant2->id]);

    // Set current tenant
    $this->tenantManager->setCurrentTenant($this->tenant1);

    // Query should only return users from tenant1
    $users = User::all();

    expect($users)->toHaveCount(1)
        ->and($users->first()->id)->toBe($user1->id);
});

it('automatically filters roles by current tenant', function (): void {
    // Create roles for both tenants
    $role1 = Role::factory()->create(['organization_id' => $this->tenant1->id]);
    $role2 = Role::factory()->create(['organization_id' => $this->tenant2->id]);

    // Set current tenant
    $this->tenantManager->setCurrentTenant($this->tenant1);

    // Query should only return roles from tenant1
    $roles = Role::all();

    expect($roles)->toHaveCount(1)
        ->and($roles->first()->id)->toBe($role1->id);
});

it('does not filter when no tenant is set', function (): void {
    // Create users for both tenants
    User::factory()->create(['organization_id' => $this->tenant1->id]);
    User::factory()->create(['organization_id' => $this->tenant2->id]);

    // Query without tenant should return all users
    $users = User::all();

    expect($users)->toHaveCount(2);
});

it('can bypass tenant scope with withoutTenantScope', function (): void {
    // Create users for both tenants
    User::factory()->create(['organization_id' => $this->tenant1->id]);
    User::factory()->create(['organization_id' => $this->tenant2->id]);

    // Set current tenant
    $this->tenantManager->setCurrentTenant($this->tenant1);

    // Query with withoutTenantScope should return all users
    $users = User::withoutTenantScope()->get();

    expect($users)->toHaveCount(2);
});

it('can query specific tenant with forTenant scope', function (): void {
    // Create users for both tenants
    $user1 = User::factory()->create(['organization_id' => $this->tenant1->id]);
    $user2 = User::factory()->create(['organization_id' => $this->tenant2->id]);

    // Set current tenant to tenant1
    $this->tenantManager->setCurrentTenant($this->tenant1);

    // Query for tenant2 specifically
    $users = User::forTenant($this->tenant2->id)->get();

    expect($users)->toHaveCount(1)
        ->and($users->first()->id)->toBe($user2->id);
});

it('automatically sets organization_id on model creation', function (): void {
    // Set current tenant
    $this->tenantManager->setCurrentTenant($this->tenant1);

    // Create user without specifying organization_id
    $user = User::factory()->create([
        'organization_id' => null,
    ]);

    expect($user->organization_id)->toBe($this->tenant1->id);
});

it('does not override manually set organization_id', function (): void {
    // Set current tenant to tenant1
    $this->tenantManager->setCurrentTenant($this->tenant1);

    // Create user with explicitly set organization_id for tenant2
    $user = User::factory()->create([
        'organization_id' => $this->tenant2->id,
    ]);

    expect($user->organization_id)->toBe($this->tenant2->id);
});

it('maintains tenant isolation across different models', function (): void {
    // Create data for both tenants
    $user1 = User::factory()->create(['organization_id' => $this->tenant1->id]);
    $user2 = User::factory()->create(['organization_id' => $this->tenant2->id]);
    $role1 = Role::factory()->create(['organization_id' => $this->tenant1->id]);
    $role2 = Role::factory()->create(['organization_id' => $this->tenant2->id]);

    // Set current tenant
    $this->tenantManager->setCurrentTenant($this->tenant1);

    // Both models should be scoped to tenant1
    expect(User::count())->toBe(1)
        ->and(Role::count())->toBe(1);
});
