<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Services\TenantManager;

if (! function_exists('tenant')) {
    /**
     * Get the TenantManager instance or current tenant
     */
    function tenant(): ?Organization
    {
        return app(TenantManager::class)->getCurrentTenant();
    }
}

if (! function_exists('tenantId')) {
    /**
     * Get the current tenant ID
     */
    function tenantId(): ?int
    {
        return app(TenantManager::class)->getCurrentTenantId();
    }
}

if (! function_exists('tenantManager')) {
    /**
     * Get the TenantManager instance
     */
    function tenantManager(): TenantManager
    {
        return app(TenantManager::class);
    }
}
