<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Provides utility methods for table name operations.
 *
 * This trait offers convenient methods to retrieve table names in different forms
 * and generate standardized pivot table names between models.
 */
trait TableNameTrait
{
    /**
     * Get the table name for this model in singular or plural form.
     *
     * This method retrieves the table name associated with the model and can
     * optionally return it in singular form. By default, it returns the plural
     * form as defined in the model's table property.
     *
     * @param  bool  $singular  Whether to return the table name in singular form (default: false)
     * @return string The table name in the requested form
     *
     * @example
     * // Returns 'users'
     * $user->tableName();
     * @example
     * // Returns 'user'
     * $user->tableName(true);
     */
    public function tableName(bool $singular = false): string
    {
        $tableName = $this->getTable();

        if ($singular) {
            return Str::singular($tableName);
        }

        return $tableName;
    }

    /**
     * Generate a standardized pivot table name between this model and another model.
     *
     * This method generates the pivot table name following Laravel's convention
     * of combining the singular forms of both table names in alphabetical order,
     * separated by an underscore.
     *
     * @param  Model|string  $model  The related model instance or class name
     * @return string The standardized pivot table name
     *
     * @throws InvalidArgumentException If the provided model is not a valid Model instance or class name
     *
     * @example
     * // Returns 'role_user'
     * $user->pivotTableName(Role::class);
     * @example
     * // Returns 'organization_user'
     * $user->pivotTableName($organization);
     */
    public function pivotTableName(Model|string $model): string
    {
        // Get the other model's table name
        if (is_string($model)) {
            if (! class_exists($model)) {
                throw new InvalidArgumentException("Class {$model} does not exist.");
            }

            if (! is_subclass_of($model, Model::class)) {
                throw new InvalidArgumentException("Class {$model} must extend Illuminate\Database\Eloquent\Model.");
            }

            $otherTable = (new $model())->getTable();
        } else {
            $otherTable = $model->getTable();
        }

        // Get this model's table name
        $thisTable = $this->getTable();

        // Get singular forms
        $table1 = Str::singular($thisTable);
        $table2 = Str::singular($otherTable);

        // Sort alphabetically and join with underscore
        $tables = [$table1, $table2];
        sort($tables);

        return implode('_', $tables);
    }
}
