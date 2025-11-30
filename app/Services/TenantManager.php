<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Organization;
use Illuminate\Support\Facades\Cache;

final class TenantManager
{
    private ?Organization $currentTenant = null;

    private bool $tenantResolved = false;

    /**
     * Resolve tenant from subdomain or domain
     */
    public function resolveTenantFromDomain(string $domain): ?Organization
    {
        // Extract subdomain from full domain
        $parts = explode('.', $domain);

        // If we have a subdomain (e.g., tenant1.example.com)
        if (count($parts) >= 3) {
            $subdomain = $parts[0];

            return $this->resolveTenantBySlug($subdomain);
        }

        // If we have a custom domain (e.g., tenant.com)
        // We could add custom domain support later
        return null;
    }

    /**
     * Resolve tenant by slug with caching
     */
    public function resolveTenantBySlug(string $slug): ?Organization
    {
        return Cache::remember(
            "tenant.slug.{$slug}",
            now()->addMinutes(60),
            fn () => Organization::query()
                ->where('slug', $slug)
                ->where('is_active', true)
                ->first()
        );
    }

    /**
     * Resolve tenant by ID with caching
     */
    public function resolveTenantById(int $id): ?Organization
    {
        return Cache::remember(
            "tenant.id.{$id}",
            now()->addMinutes(60),
            fn () => Organization::query()
                ->where('id', $id)
                ->where('is_active', true)
                ->first()
        );
    }

    /**
     * Set the current tenant
     */
    public function setCurrentTenant(?Organization $tenant): void
    {
        $this->currentTenant = $tenant;
        $this->tenantResolved = true;
    }

    /**
     * Get the current tenant
     */
    public function getCurrentTenant(): ?Organization
    {
        return $this->currentTenant;
    }

    /**
     * Check if a tenant has been resolved
     */
    public function hasTenant(): bool
    {
        return $this->currentTenant instanceof Organization;
    }

    /**
     * Check if tenant resolution has been attempted
     */
    public function isResolved(): bool
    {
        return $this->tenantResolved;
    }

    /**
     * Clear the current tenant (for admin switching)
     */
    public function clearTenant(): void
    {
        $this->currentTenant = null;
        $this->tenantResolved = false;
    }

    /**
     * Get the current tenant ID or null
     */
    public function getCurrentTenantId(): ?int
    {
        return $this->currentTenant?->id;
    }

    /**
     * Switch to a different tenant (for admin users)
     */
    public function switchTenant(int $tenantId): bool
    {
        $tenant = $this->resolveTenantById($tenantId);

        if ($tenant instanceof Organization) {
            $this->setCurrentTenant($tenant);

            return true;
        }

        return false;
    }

    /**
     * Clear tenant cache
     */
    public function clearTenantCache(Organization $organization): void
    {
        Cache::forget("tenant.slug.{$organization->slug}");
        Cache::forget("tenant.id.{$organization->id}");
    }
}
