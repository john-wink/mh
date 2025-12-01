<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

final class TestTenantSeeder extends Seeder
{
    /**
     * Seed test tenants with different scenarios
     */
    public function run(): void
    {
        // Create global permissions (not tenant-specific)
        $permissions = $this->createPermissions();

        // Scenario 1: Small organization with basic roles
        $this->createSmallOrganization($permissions);

        // Scenario 2: Medium organization with department structure
        $this->createMediumOrganization($permissions);

        // Scenario 3: Large organization with complex hierarchy
        $this->createLargeOrganization($permissions);

        // Scenario 4: Inactive organization
        $this->createInactiveOrganization($permissions);
    }

    /**
     * Create global permissions
     *
     * @return array<string, Permission>
     */
    private function createPermissions(): array
    {
        $permissionData = [
            'super-admin' => 'Super Admin',
            'organizations.view' => 'View Organizations',
            'organizations.create' => 'Create Organizations',
            'organizations.update' => 'Update Organizations',
            'organizations.delete' => 'Delete Organizations',
            'users.view' => 'View Users',
            'users.create' => 'Create Users',
            'users.update' => 'Update Users',
            'users.delete' => 'Delete Users',
            'roles.view' => 'View Roles',
            'roles.create' => 'Create Roles',
            'roles.update' => 'Update Roles',
            'roles.delete' => 'Delete Roles',
            'permissions.view' => 'View Permissions',
            'permissions.create' => 'Create Permissions',
            'permissions.update' => 'Update Permissions',
            'permissions.delete' => 'Delete Permissions',
        ];

        $permissions = [];

        foreach ($permissionData as $slug => $name) {
            $permissions[$slug] = Permission::query()->create([
                'slug' => $slug,
                'name' => $name,
            ]);
        }

        return $permissions;
    }

    /**
     * Create a small organization with basic roles
     *
     * @param  array<string, Permission>  $permissions
     */
    private function createSmallOrganization(array $permissions): void
    {
        $org = Organization::query()->create([
            'name' => 'Small Tech Startup',
            'slug' => 'small-tech',
            'is_active' => true,
        ]);

        // Admin role with full permissions
        $adminRole = Role::query()->create([
            'organization_id' => $org->id,
            'name' => 'Administrator',
            'slug' => 'admin',
            'description' => 'Full access to all resources',
        ]);
        $adminRole->permissions()->attach([
            $permissions['users.view']->id,
            $permissions['users.create']->id,
            $permissions['users.update']->id,
            $permissions['users.delete']->id,
            $permissions['roles.view']->id,
            $permissions['roles.create']->id,
            $permissions['roles.update']->id,
        ]);

        // User role with limited permissions
        $userRole = Role::query()->create([
            'organization_id' => $org->id,
            'name' => 'User',
            'slug' => 'user',
            'description' => 'Basic user access',
        ]);
        $userRole->permissions()->attach([
            $permissions['users.view']->id,
        ]);

        // Create users
        $admin = User::query()->create([
            'organization_id' => $org->id,
            'name' => 'Admin User',
            'email' => 'admin@small-tech.test',
            'password' => 'password',
            'email_verified_at' => now(),
        ]);
        $admin->roles()->attach($adminRole);

        $users = User::factory()->count(3)->create([
            'organization_id' => $org->id,
            'email_verified_at' => now(),
        ]);

        foreach ($users as $user) {
            $user->roles()->attach($userRole);
        }
    }

    /**
     * Create a medium organization with department structure
     *
     * @param  array<string, Permission>  $permissions
     */
    private function createMediumOrganization(array $permissions): void
    {
        $org = Organization::query()->create([
            'name' => 'Medium Enterprise Corp',
            'slug' => 'medium-corp',
            'is_active' => true,
        ]);

        // Create roles for different departments
        $adminRole = Role::query()->create([
            'organization_id' => $org->id,
            'name' => 'Administrator',
            'slug' => 'admin',
            'description' => 'Organization administrator',
        ]);
        $adminRole->permissions()->attach([
            $permissions['users.view']->id,
            $permissions['users.create']->id,
            $permissions['users.update']->id,
            $permissions['users.delete']->id,
            $permissions['roles.view']->id,
            $permissions['roles.create']->id,
            $permissions['roles.update']->id,
            $permissions['roles.delete']->id,
        ]);

        $managerRole = Role::query()->create([
            'organization_id' => $org->id,
            'name' => 'Department Manager',
            'slug' => 'manager',
            'description' => 'Can manage users in their department',
        ]);
        $managerRole->permissions()->attach([
            $permissions['users.view']->id,
            $permissions['users.create']->id,
            $permissions['users.update']->id,
            $permissions['roles.view']->id,
        ]);

        $employeeRole = Role::query()->create([
            'organization_id' => $org->id,
            'name' => 'Employee',
            'slug' => 'employee',
            'description' => 'Standard employee access',
        ]);
        $employeeRole->permissions()->attach([
            $permissions['users.view']->id,
        ]);

        // Create users
        $admin = User::query()->create([
            'organization_id' => $org->id,
            'name' => 'Corp Admin',
            'email' => 'admin@medium-corp.test',
            'password' => 'password',
            'email_verified_at' => now(),
        ]);
        $admin->roles()->attach($adminRole);

        $managers = User::factory()->count(3)->create([
            'organization_id' => $org->id,
            'email_verified_at' => now(),
        ]);

        foreach ($managers as $manager) {
            $manager->roles()->attach($managerRole);
        }

        $employees = User::factory()->count(15)->create([
            'organization_id' => $org->id,
            'email_verified_at' => now(),
        ]);

        foreach ($employees as $employee) {
            $employee->roles()->attach($employeeRole);
        }
    }

    /**
     * Create a large organization with complex hierarchy
     *
     * @param  array<string, Permission>  $permissions
     */
    private function createLargeOrganization(array $permissions): void
    {
        $org = Organization::query()->create([
            'name' => 'Large Global Corporation',
            'slug' => 'global-corp',
            'is_active' => true,
        ]);

        // Create super admin role
        $superAdminRole = Role::query()->create([
            'organization_id' => $org->id,
            'name' => 'Super Administrator',
            'slug' => 'super-admin',
            'description' => 'Full system access',
        ]);
        $superAdminRole->permissions()->attach($permissions['super-admin']);

        // Executive role
        $executiveRole = Role::query()->create([
            'organization_id' => $org->id,
            'name' => 'Executive',
            'slug' => 'executive',
            'description' => 'Executive level access',
        ]);
        $executiveRole->permissions()->attach([
            $permissions['users.view']->id,
            $permissions['users.update']->id,
            $permissions['roles.view']->id,
        ]);

        // Director role
        $directorRole = Role::query()->create([
            'organization_id' => $org->id,
            'name' => 'Director',
            'slug' => 'director',
            'description' => 'Departmental director',
        ]);
        $directorRole->permissions()->attach([
            $permissions['users.view']->id,
            $permissions['users.create']->id,
            $permissions['users.update']->id,
            $permissions['roles.view']->id,
        ]);

        // Manager role
        $managerRole = Role::query()->create([
            'organization_id' => $org->id,
            'name' => 'Manager',
            'slug' => 'manager',
            'description' => 'Team manager',
        ]);
        $managerRole->permissions()->attach([
            $permissions['users.view']->id,
            $permissions['users.update']->id,
        ]);

        // Team lead role
        $teamLeadRole = Role::query()->create([
            'organization_id' => $org->id,
            'name' => 'Team Lead',
            'slug' => 'team-lead',
            'description' => 'Leads a team',
        ]);
        $teamLeadRole->permissions()->attach([
            $permissions['users.view']->id,
        ]);

        // Employee role
        $employeeRole = Role::query()->create([
            'organization_id' => $org->id,
            'name' => 'Employee',
            'slug' => 'employee',
            'description' => 'Standard employee',
        ]);
        $employeeRole->permissions()->attach([
            $permissions['users.view']->id,
        ]);

        // Create users
        $superAdmin = User::query()->create([
            'organization_id' => $org->id,
            'name' => 'Super Admin',
            'email' => 'superadmin@global-corp.test',
            'password' => 'password',
            'email_verified_at' => now(),
        ]);
        $superAdmin->roles()->attach($superAdminRole);

        $executives = User::factory()->count(2)->create([
            'organization_id' => $org->id,
            'email_verified_at' => now(),
        ]);
        foreach ($executives as $exec) {
            $exec->roles()->attach($executiveRole);
        }

        $directors = User::factory()->count(5)->create([
            'organization_id' => $org->id,
            'email_verified_at' => now(),
        ]);
        foreach ($directors as $director) {
            $director->roles()->attach($directorRole);
        }

        $managers = User::factory()->count(10)->create([
            'organization_id' => $org->id,
            'email_verified_at' => now(),
        ]);
        foreach ($managers as $manager) {
            $manager->roles()->attach($managerRole);
        }

        $teamLeads = User::factory()->count(20)->create([
            'organization_id' => $org->id,
            'email_verified_at' => now(),
        ]);
        foreach ($teamLeads as $lead) {
            $lead->roles()->attach($teamLeadRole);
        }

        $employees = User::factory()->count(100)->create([
            'organization_id' => $org->id,
            'email_verified_at' => now(),
        ]);
        foreach ($employees as $employee) {
            $employee->roles()->attach($employeeRole);
        }
    }

    /**
     * Create an inactive organization for testing
     *
     * @param  array<string, Permission>  $permissions
     */
    private function createInactiveOrganization(array $permissions): void
    {
        $org = Organization::query()->create([
            'name' => 'Inactive Organization',
            'slug' => 'inactive-org',
            'is_active' => false,
        ]);

        $adminRole = Role::query()->create([
            'organization_id' => $org->id,
            'name' => 'Administrator',
            'slug' => 'admin',
        ]);
        $adminRole->permissions()->attach([
            $permissions['users.view']->id,
            $permissions['users.create']->id,
        ]);

        $admin = User::query()->create([
            'organization_id' => $org->id,
            'name' => 'Inactive Admin',
            'email' => 'admin@inactive-org.test',
            'password' => 'password',
            'email_verified_at' => now(),
        ]);
        $admin->roles()->attach($adminRole);

        User::factory()->count(5)->create([
            'organization_id' => $org->id,
            'email_verified_at' => now(),
        ])->each(fn (User $user) => $user->roles()->attach($adminRole));
    }
}
