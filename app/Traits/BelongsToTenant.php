<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Organization;
use App\Scopes\TenantScope;
use App\Services\TenantManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    /**
     * Get the organization that owns the model
     *
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Boot the trait
     */
    protected static function bootBelongsToTenant(): void
    {
        // Automatically apply tenant scope
        static::addGlobalScope(app(TenantScope::class));

        // Automatically set organization_id on create
        static::creating(function ($model): void {
            $tenantManager = app(TenantManager::class);

            if ($tenantManager->hasTenant() && ! isset($model->organization_id)) {
                $model->organization_id = $tenantManager->getCurrentTenantId();
            }
        });
    }

    /**
     * Scope a query to exclude tenant filtering
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    protected function scopeWithoutTenantScope($query)
    {
        return $query->withoutGlobalScope(TenantScope::class);
    }

    /**
     * Scope a query to a specific tenant
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    protected function scopeForTenant($query, int $organizationId)
    {
        return $query->withoutGlobalScope(TenantScope::class)
            ->where('organization_id', $organizationId);
    }
}
