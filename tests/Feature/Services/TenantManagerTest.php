<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Services\TenantManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenantManager = app(TenantManager::class);
});

it('can resolve tenant from domain', function (): void {
    $organization = Organization::factory()->create(['slug' => 'test-tenant']);

    $resolved = $this->tenantManager->resolveTenantFromDomain('test-tenant.example.com');

    expect($resolved)->toBeInstanceOf(Organization::class)
        ->and($resolved->id)->toBe($organization->id);
});

it('returns null for invalid domain', function (): void {
    $resolved = $this->tenantManager->resolveTenantFromDomain('invalid.example.com');

    expect($resolved)->toBeNull();
});

it('can resolve tenant by slug', function (): void {
    $organization = Organization::factory()->create(['slug' => 'test-tenant']);

    $resolved = $this->tenantManager->resolveTenantBySlug('test-tenant');

    expect($resolved)->toBeInstanceOf(Organization::class)
        ->and($resolved->id)->toBe($organization->id);
});

it('caches tenant resolution by slug', function (): void {
    $organization = Organization::factory()->create(['slug' => 'test-tenant']);

    // First call should query the database
    $this->tenantManager->resolveTenantBySlug('test-tenant');

    // Second call should use cache
    expect(Cache::has('tenant.slug.test-tenant'))->toBeTrue();
});

it('can resolve tenant by id', function (): void {
    $organization = Organization::factory()->create();

    $resolved = $this->tenantManager->resolveTenantById($organization->id);

    expect($resolved)->toBeInstanceOf(Organization::class)
        ->and($resolved->id)->toBe($organization->id);
});

it('only resolves active tenants', function (): void {
    $organization = Organization::factory()->create([
        'slug' => 'inactive-tenant',
        'is_active' => false,
    ]);

    $resolved = $this->tenantManager->resolveTenantBySlug('inactive-tenant');

    expect($resolved)->toBeNull();
});

it('can set and get current tenant', function (): void {
    $organization = Organization::factory()->create();

    $this->tenantManager->setCurrentTenant($organization);

    expect($this->tenantManager->getCurrentTenant())->toBe($organization)
        ->and($this->tenantManager->hasTenant())->toBeTrue()
        ->and($this->tenantManager->isResolved())->toBeTrue();
});

it('can get current tenant id', function (): void {
    $organization = Organization::factory()->create();

    $this->tenantManager->setCurrentTenant($organization);

    expect($this->tenantManager->getCurrentTenantId())->toBe($organization->id);
});

it('returns null for tenant id when no tenant set', function (): void {
    expect($this->tenantManager->getCurrentTenantId())->toBeNull();
});

it('can clear current tenant', function (): void {
    $organization = Organization::factory()->create();

    $this->tenantManager->setCurrentTenant($organization);
    $this->tenantManager->clearTenant();

    expect($this->tenantManager->getCurrentTenant())->toBeNull()
        ->and($this->tenantManager->hasTenant())->toBeFalse()
        ->and($this->tenantManager->isResolved())->toBeFalse();
});

it('can switch tenant', function (): void {
    $tenant1 = Organization::factory()->create();
    $tenant2 = Organization::factory()->create();

    $this->tenantManager->setCurrentTenant($tenant1);

    $success = $this->tenantManager->switchTenant($tenant2->id);

    expect($success)->toBeTrue()
        ->and($this->tenantManager->getCurrentTenantId())->toBe($tenant2->id);
});

it('returns false when switching to invalid tenant', function (): void {
    $success = $this->tenantManager->switchTenant(999);

    expect($success)->toBeFalse();
});

it('can clear tenant cache', function (): void {
    $organization = Organization::factory()->create(['slug' => 'test-tenant']);

    // Populate cache
    $this->tenantManager->resolveTenantBySlug('test-tenant');
    $this->tenantManager->resolveTenantById($organization->id);

    // Clear cache
    $this->tenantManager->clearTenantCache($organization);

    expect(Cache::has('tenant.slug.test-tenant'))->toBeFalse()
        ->and(Cache::has("tenant.id.{$organization->id}"))->toBeFalse();
});
