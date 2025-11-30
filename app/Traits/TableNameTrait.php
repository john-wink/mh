<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Support\Str;

trait TableNameTrait
{
    public static function tableName(bool $singular = false): string
    {
        $class = static::class;
        if ($singular) {
            return Str::of(new $class()->getTable())->singular()->toString();
        }

        return new $class()->getTable();
    }

    public static function pivotTableName($model): string
    {
        $tables = array_map('Str::singular', [self::tableName(), $model::tableName()]);
        sort($tables);

        return implode('_', $tables);
    }
}
