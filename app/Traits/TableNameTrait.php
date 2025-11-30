<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use InvalidArgumentException;

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
        // Handle string class names
        if (is_string($model)) {
            throw_unless(class_exists($model), new InvalidArgumentException("Class {$model} does not exist."));
            throw_unless(is_subclass_of($model, Model::class), new InvalidArgumentException("Class {$model} must extend Illuminate\Database\Eloquent\Model."));
        } elseif (is_object($model)) {
            throw_unless($model instanceof Model, new InvalidArgumentException('Provided object must extend Illuminate\Database\Eloquent\Model.'));
            $model = get_class($model);
        } else {
            throw new InvalidArgumentException('Model must be a class name string or Model instance.');
        }

        $tables = array_map('Str::singular', [self::tableName(), $model::tableName()]);
        sort($tables);

        return implode('_', $tables);
    }
}
