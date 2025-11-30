<?php

declare(strict_types=1);

use App\Http\Middleware\TenantResolver;
use App\Models\Organization;
use App\Services\TenantManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenantManager = app(TenantManager::class);

    // Clear tenant state before each test
    $this->tenantManager->clearTenant();

    // Create a test route with the middleware
    Route::get('/test-tenant-route', function () {
        return response()->json([
            'tenant_id' => tenantId(),
            'has_tenant' => app(TenantManager::class)->hasTenant(),
        ]);
    })->middleware(TenantResolver::class);
});

it('resolves tenant from subdomain', function (): void {
    $organization = Organization::factory()->create(['slug' => 'test-tenant']);

    $response = $this->get('http://test-tenant.example.com/test-tenant-route');

    $response->assertSuccessful()
        ->assertJson([
            'tenant_id' => $organization->id,
            'has_tenant' => true,
        ]);
});

it('returns 404 for invalid subdomain', function (): void {
    $response = $this->get('http://invalid-tenant.example.com/test-tenant-route');

    $response->assertNotFound();
});

it('allows access on localhost without tenant', function (): void {
    $response = $this->get('http://localhost/test-tenant-route');

    $response->assertSuccessful()
        ->assertJson([
            'tenant_id' => null,
            'has_tenant' => false,
        ]);
});

it('skips resolution if already resolved', function (): void {
    $organization = Organization::factory()->create(['slug' => 'test-tenant']);

    // Manually set tenant before request
    $this->tenantManager->setCurrentTenant($organization);

    $response = $this->get('http://different-tenant.example.com/test-tenant-route');

    $response->assertSuccessful()
        ->assertJson([
            'tenant_id' => $organization->id,
            'has_tenant' => true,
        ]);
});

it('only resolves active tenants', function (): void {
    $organization = Organization::factory()->create([
        'slug' => 'inactive-tenant',
        'is_active' => false,
    ]);

    $response = $this->get('http://inactive-tenant.example.com/test-tenant-route');

    $response->assertNotFound();
});
