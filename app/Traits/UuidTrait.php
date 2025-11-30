<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait UuidTrait
{
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected static function bootUuidTrait(): void
    {
        static::creating(function (Model $model): void {
            // Only generate UUID if it hasn't been set
            if (blank($model->uuid)) {
                $model->uuid = (string) Str::uuid7();
            }
        });

        static::updating(function (Model $model): void {
            // Prevent UUID changes after creation
            if ($model->isDirty('uuid') && filled($model->getOriginal('uuid'))) {
                $model->uuid = $model->getOriginal('uuid');
            }
        });

        static::replicating(function (Model $model): void {
            // Reset UUID for replicated models to get a new one
            $model->uuid = null;
        });
    }

    protected function initializeUuidTrait(): void
    {
        // Generate UUID for new instances that don't have one
        if (! $this->exists && blank($this->uuid)) {
            $this->uuid = (string) Str::uuid7();
        }
    }
}
