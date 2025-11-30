<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Traits\TableNameTrait;
use Illuminate\Database\Eloquent\Model;

// Test tableName() method with default parameter (plural)
test('tableName returns plural table name by default', function (): void {
    $user = new User();

    expect($user->tableName())->toBe('users');
});

// Test tableName() method with singular parameter
test('tableName returns singular table name when requested', function (): void {
    $user = new User();

    expect($user->tableName(true))->toBe('user');
});

// Test tableName() with different models
test('tableName works with different models', function (): void {
    $organization = new Organization();
    $role = new Role();

    expect($organization->tableName())->toBe('organizations')
        ->and($organization->tableName(true))->toBe('organization')
        ->and($role->tableName())->toBe('roles')
        ->and($role->tableName(true))->toBe('role');
});

// Test pivotTableName() with model class string
test('pivotTableName generates correct pivot table name with model class string', function (): void {
    $user = new User();

    expect($user->pivotTableName(Role::class))->toBe('role_user');
});

// Test pivotTableName() with model instance
test('pivotTableName generates correct pivot table name with model instance', function (): void {
    $user = new User();
    $role = new Role();

    expect($user->pivotTableName($role))->toBe('role_user');
});

// Test pivotTableName() alphabetical ordering
test('pivotTableName maintains alphabetical order', function (): void {
    $user = new User();
    $organization = new Organization();

    // Should be 'organization_user' not 'user_organization'
    expect($user->pivotTableName(Organization::class))->toBe('organization_user')
        ->and($organization->pivotTableName(User::class))->toBe('organization_user');
});

// Test pivotTableName() with various model combinations
test('pivotTableName works with various model combinations', function (): void {
    $user = new User();
    $role = new Role();
    $organization = new Organization();

    expect($user->pivotTableName(Role::class))->toBe('role_user')
        ->and($role->pivotTableName(User::class))->toBe('role_user')
        ->and($organization->pivotTableName(Role::class))->toBe('organization_role')
        ->and($role->pivotTableName(Organization::class))->toBe('organization_role');
});

// Test pivotTableName() throws exception for non-existent class
test('pivotTableName throws exception for non-existent class', function (): void {
    $user = new User();

    $user->pivotTableName('NonExistentClass');
})->throws(InvalidArgumentException::class, 'Class NonExistentClass does not exist.');

// Test pivotTableName() throws exception for non-model class
test('pivotTableName throws exception for non-model class', function (): void {
    $user = new User();

    $user->pivotTableName(stdClass::class);
})->throws(InvalidArgumentException::class, 'must extend Illuminate\Database\Eloquent\Model');

// Test trait can be used in custom models
test('trait can be used in custom models', function (): void {
    // Create a test model class that uses the trait
    $testModel = new class() extends Model
    {
        use TableNameTrait;

        protected $table = 'test_models';
    };

    expect($testModel->tableName())->toBe('test_models')
        ->and($testModel->tableName(true))->toBe('test_model');
});

// Test pivotTableName with same model (edge case)
test('pivotTableName works with same model type', function (): void {
    $user1 = new User();
    $user2 = new User();

    expect($user1->pivotTableName($user2))->toBe('user_user');
});

// Test tableName with explicit false parameter
test('tableName with explicit false parameter returns plural', function (): void {
    $user = new User();

    expect($user->tableName(false))->toBe('users');
});

// Test pivotTableName maintains consistency
test('pivotTableName is consistent regardless of call order', function (): void {
    $user = new User();
    $role = new Role();

    $pivot1 = $user->pivotTableName($role);
    $pivot2 = $role->pivotTableName($user);

    expect($pivot1)->toBe($pivot2)
        ->and($pivot1)->toBe('role_user');
});
