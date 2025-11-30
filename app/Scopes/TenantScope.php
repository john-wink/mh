<?php

declare(strict_types=1);

namespace App\Scopes;

use App\Services\TenantManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

final class TenantScope implements Scope
{
    public function __construct(
        private readonly TenantManager $tenantManager
    ) {}

    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Only apply if tenant is resolved and model has organization_id column
        if (! $this->tenantManager->hasTenant()) {
            return;
        }

        $tenantId = $this->tenantManager->getCurrentTenantId();

        // Check if the model has organization_id column
        if (! in_array('organization_id', $model->getFillable()) &&
            ! in_array('organization_id', array_keys($model->getCasts()))) {
            return;
        }

        $builder->where($model->getTable().'.organization_id', $tenantId);
    }
}
