<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    DB::transaction(function (): void {
        $this->organization = Organization::factory()->create();
        $this->otherOrganization = Organization::factory()->create();

        // Create super admin permission
        $this->superAdminPermission = Permission::factory()->create([
            'slug' => 'super-admin',
            'name' => 'Super Admin',
        ]);

        // Create organization permissions
        $this->organizationsViewPermission = Permission::factory()->create([
            'slug' => 'organizations.view',
            'name' => 'View Organizations',
        ]);

        $this->organizationsUpdatePermission = Permission::factory()->create([
            'slug' => 'organizations.update',
            'name' => 'Update Organizations',
        ]);

        // Create user permissions
        $this->usersViewPermission = Permission::factory()->create([
            'slug' => 'users.view',
            'name' => 'View Users',
        ]);

        $this->usersCreatePermission = Permission::factory()->create([
            'slug' => 'users.create',
            'name' => 'Create Users',
        ]);

        $this->usersUpdatePermission = Permission::factory()->create([
            'slug' => 'users.update',
            'name' => 'Update Users',
        ]);

        $this->usersDeletePermission = Permission::factory()->create([
            'slug' => 'users.delete',
            'name' => 'Delete Users',
        ]);

        // Create super admin role
        $this->superAdminRole = Role::factory()->for($this->organization)->create([
            'slug' => 'super-admin',
            'name' => 'Super Admin',
        ]);
        $this->superAdminRole->permissions()->attach($this->superAdminPermission);

        // Create organization admin role
        $this->orgAdminRole = Role::factory()->for($this->organization)->create([
            'slug' => 'organization-admin',
            'name' => 'Organization Admin',
        ]);
        $this->orgAdminRole->permissions()->attach([
            $this->organizationsViewPermission->id,
            $this->organizationsUpdatePermission->id,
            $this->usersViewPermission->id,
            $this->usersCreatePermission->id,
            $this->usersUpdatePermission->id,
            $this->usersDeletePermission->id,
        ]);

        // Create regular user role
        $this->userRole = Role::factory()->for($this->organization)->create([
            'slug' => 'user',
            'name' => 'User',
        ]);
        $this->userRole->permissions()->attach([
            $this->usersViewPermission->id,
        ]);
    }, 3);

});

it('allows super admin to view any organization', function (): void {
    $superAdmin = User::factory()->for($this->organization)->create();
    $superAdmin->roles()->attach($this->superAdminRole);

    expect($superAdmin->can('view', $this->otherOrganization))->toBeTrue();
});

it('allows organization admin to view their own organization', function (): void {
    $orgAdmin = User::factory()->for($this->organization)->create();
    $orgAdmin->roles()->attach($this->orgAdminRole);

    expect($orgAdmin->can('view', $this->organization))->toBeTrue();
});

it('prevents organization admin from viewing other organizations', function (): void {
    $orgAdmin = User::factory()->for($this->organization)->create();
    $orgAdmin->roles()->attach($this->orgAdminRole);

    expect($orgAdmin->can('view', $this->otherOrganization))->toBeFalse();
});

it('allows super admin to create organizations', function (): void {
    $superAdmin = User::factory()->for($this->organization)->create();
    $superAdmin->roles()->attach($this->superAdminRole);

    expect($superAdmin->can('create', Organization::class))->toBeTrue();
});

it('prevents organization admin from creating organizations', function (): void {
    $orgAdmin = User::factory()->for($this->organization)->create();
    $orgAdmin->roles()->attach($this->orgAdminRole);

    expect($orgAdmin->can('create', Organization::class))->toBeFalse();
});

it('allows organization admin to update their own organization', function (): void {
    $orgAdmin = User::factory()->for($this->organization)->create();
    $orgAdmin->roles()->attach($this->orgAdminRole);

    expect($orgAdmin->can('update', $this->organization))->toBeTrue();
});

it('prevents organization admin from updating other organizations', function (): void {
    $orgAdmin = User::factory()->for($this->organization)->create();
    $orgAdmin->roles()->attach($this->orgAdminRole);

    expect($orgAdmin->can('update', $this->otherOrganization))->toBeFalse();
});

it('allows user to view their own profile', function (): void {
    $user = User::factory()->for($this->organization)->create();
    $user->roles()->attach($this->userRole);

    expect($user->can('view', $user))->toBeTrue();
});

it('allows organization admin to view users in their organization', function (): void {
    $orgAdmin = User::factory()->for($this->organization)->create();
    $orgAdmin->roles()->attach($this->orgAdminRole);

    $otherUser = User::factory()->for($this->organization)->create();

    expect($orgAdmin->can('view', $otherUser))->toBeTrue();
});

it('prevents organization admin from viewing users in other organizations', function (): void {
    $orgAdmin = User::factory()->for($this->organization)->create();
    $orgAdmin->roles()->attach($this->orgAdminRole);

    $otherOrgUser = User::factory()->for($this->otherOrganization)->create();

    expect($orgAdmin->can('view', $otherOrgUser))->toBeFalse();
});

it('allows organization admin to create users', function (): void {
    $orgAdmin = User::factory()->for($this->organization)->create();
    $orgAdmin->roles()->attach($this->orgAdminRole);

    expect($orgAdmin->can('create', User::class))->toBeTrue();
});

it('prevents regular user from creating users', function (): void {
    $user = User::factory()->for($this->organization)->create();
    $user->roles()->attach($this->userRole);

    expect($user->can('create', User::class))->toBeFalse();
});

it('allows user to update their own profile', function (): void {
    $user = User::factory()->for($this->organization)->create();
    $user->roles()->attach($this->userRole);

    expect($user->can('update', $user))->toBeTrue();
});

it('allows organization admin to update users in their organization', function (): void {
    $orgAdmin = User::factory()->for($this->organization)->create();
    $orgAdmin->roles()->attach($this->orgAdminRole);

    $otherUser = User::factory()->for($this->organization)->create();

    expect($orgAdmin->can('update', $otherUser))->toBeTrue();
});

it('prevents organization admin from updating users in other organizations', function (): void {
    $orgAdmin = User::factory()->for($this->organization)->create();
    $orgAdmin->roles()->attach($this->orgAdminRole);

    $otherOrgUser = User::factory()->for($this->otherOrganization)->create();

    expect($orgAdmin->can('update', $otherOrgUser))->toBeFalse();
});

it('prevents user from deleting themselves', function (): void {
    $user = User::factory()->for($this->organization)->create();
    $user->roles()->attach($this->userRole);

    expect($user->can('delete', $user))->toBeFalse();
});

it('allows organization admin to delete users in their organization', function (): void {
    $orgAdmin = User::factory()->for($this->organization)->create();
    $orgAdmin->roles()->attach($this->orgAdminRole);

    $otherUser = User::factory()->for($this->organization)->create();

    expect($orgAdmin->can('delete', $otherUser))->toBeTrue();
});

it('prevents organization admin from deleting users in other organizations', function (): void {
    $orgAdmin = User::factory()->for($this->organization)->create();
    $orgAdmin->roles()->attach($this->orgAdminRole);

    $otherOrgUser = User::factory()->for($this->otherOrganization)->create();

    expect($orgAdmin->can('delete', $otherOrgUser))->toBeFalse();
});

it('allows super admin to force delete any user', function (): void {
    $superAdmin = User::factory()->for($this->organization)->create();
    $superAdmin->roles()->attach($this->superAdminRole);

    $otherOrgUser = User::factory()->for($this->otherOrganization)->create();

    expect($superAdmin->can('forceDelete', $otherOrgUser))->toBeTrue();
});

it('prevents organization admin from force deleting users', function (): void {
    $orgAdmin = User::factory()->for($this->organization)->create();
    $orgAdmin->roles()->attach($this->orgAdminRole);

    $otherUser = User::factory()->for($this->organization)->create();

    expect($orgAdmin->can('forceDelete', $otherUser))->toBeFalse();
});
