<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->organization = Organization::factory()->create();

    // Create permissions
    $this->adminPermission = Permission::factory()->create([
        'slug' => 'admin',
        'name' => 'Admin',
    ]);

    $this->editorPermission = Permission::factory()->create([
        'slug' => 'editor',
        'name' => 'Editor',
    ]);

    // Create admin role
    $this->adminRole = Role::factory()->for($this->organization)->create([
        'slug' => 'admin',
        'name' => 'Admin',
    ]);
    $this->adminRole->permissions()->attach($this->adminPermission);

    // Create editor role
    $this->editorRole = Role::factory()->for($this->organization)->create([
        'slug' => 'editor',
        'name' => 'Editor',
    ]);
    $this->editorRole->permissions()->attach($this->editorPermission);

    // Define test routes using API middleware for JSON responses
    Route::middleware(['api', 'auth:sanctum', 'role:admin'])->get('/api/test-role-admin', function (): array {
        return ['message' => 'admin access granted'];
    });

    Route::middleware(['api', 'auth:sanctum', 'role:editor,admin'])->get('/api/test-role-multi', function (): array {
        return ['message' => 'editor or admin access granted'];
    });

    Route::middleware(['api', 'auth:sanctum', 'permission:admin'])->get('/api/test-permission-admin', function (): array {
        return ['message' => 'admin permission granted'];
    });

    Route::middleware(['api', 'auth:sanctum', 'permission:editor,admin'])->get('/api/test-permission-multi', function (): array {
        return ['message' => 'editor or admin permission granted'];
    });
});

it('allows access to users with the required role', function (): void {
    $admin = User::factory()->for($this->organization)->create();
    $admin->roles()->attach($this->adminRole);
    $token = $admin->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->get('/api/test-role-admin')
        ->assertOk()
        ->assertJson(['message' => 'admin access granted']);
});

it('denies access to users without the required role', function (): void {
    $editor = User::factory()->for($this->organization)->create();
    $editor->roles()->attach($this->editorRole);
    $token = $editor->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->get('/api/test-role-admin')
        ->assertForbidden();
});

it('allows access to users with any of the required roles', function (): void {
    $editor = User::factory()->for($this->organization)->create();
    $editor->roles()->attach($this->editorRole);
    $editorToken = $editor->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$editorToken)
        ->get('/api/test-role-multi')
        ->assertOk()
        ->assertJson(['message' => 'editor or admin access granted']);

    $admin = User::factory()->for($this->organization)->create();
    $admin->roles()->attach($this->adminRole);
    $adminToken = $admin->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$adminToken)
        ->get('/api/test-role-multi')
        ->assertOk()
        ->assertJson(['message' => 'editor or admin access granted']);
});

it('denies access to unauthenticated users when checking role', function (): void {
    $this->getJson('/api/test-role-admin')
        ->assertUnauthorized();
});

it('allows access to users with the required permission', function (): void {
    $admin = User::factory()->for($this->organization)->create();
    $admin->roles()->attach($this->adminRole);
    $token = $admin->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->get('/api/test-permission-admin')
        ->assertOk()
        ->assertJson(['message' => 'admin permission granted']);
});

it('denies access to users without the required permission', function (): void {
    $editor = User::factory()->for($this->organization)->create();
    $editor->roles()->attach($this->editorRole);
    $token = $editor->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->get('/api/test-permission-admin')
        ->assertForbidden();
});

it('allows access to users with any of the required permissions', function (): void {
    $editor = User::factory()->for($this->organization)->create();
    $editor->roles()->attach($this->editorRole);
    $editorToken = $editor->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$editorToken)
        ->get('/api/test-permission-multi')
        ->assertOk()
        ->assertJson(['message' => 'editor or admin permission granted']);

    $admin = User::factory()->for($this->organization)->create();
    $admin->roles()->attach($this->adminRole);
    $adminToken = $admin->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$adminToken)
        ->get('/api/test-permission-multi')
        ->assertOk()
        ->assertJson(['message' => 'editor or admin permission granted']);
});

it('denies access to unauthenticated users when checking permission', function (): void {
    $this->getJson('/api/test-permission-admin')
        ->assertUnauthorized();
});
