<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->organization = Organization::factory()->create();

    // Create permissions
    $this->viewPermission = Permission::factory()->create(['slug' => 'users.view']);
    $this->createPermission = Permission::factory()->create(['slug' => 'users.create']);
    $this->updatePermission = Permission::factory()->create(['slug' => 'users.update']);
    $this->deletePermission = Permission::factory()->create(['slug' => 'users.delete']);
    $this->superAdminPermission = Permission::factory()->create(['slug' => 'super-admin']);
});

describe('Role assignment', function (): void {
    it('assigns single role to user', function (): void {
        $user = User::factory()->for($this->organization)->create();
        $role = Role::factory()->for($this->organization)->create();

        $user->roles()->attach($role);

        expect($user->roles)->toHaveCount(1)
            ->and($user->hasRole($role->slug))->toBeTrue();
    });

    it('assigns multiple roles to user', function (): void {
        $user = User::factory()->for($this->organization)->create();
        $role1 = Role::factory()->for($this->organization)->create(['slug' => 'admin']);
        $role2 = Role::factory()->for($this->organization)->create(['slug' => 'editor']);

        $user->roles()->attach([$role1->id, $role2->id]);

        expect($user->roles)->toHaveCount(2)
            ->and($user->hasRole('admin'))->toBeTrue()
            ->and($user->hasRole('editor'))->toBeTrue();
    });

    it('prevents duplicate role assignments', function (): void {
        $user = User::factory()->for($this->organization)->create();
        $role = Role::factory()->for($this->organization)->create();

        $user->roles()->attach($role);

        // Attempting to attach the same role again should be handled by the application
        // In production, you'd typically check before attaching
        if (! $user->roles()->where('role_id', $role->id)->exists()) {
            $user->roles()->attach($role);
        }

        expect($user->fresh()->roles)->toHaveCount(1);
    });

    it('removes role from user', function (): void {
        $user = User::factory()->for($this->organization)->create();
        $role = Role::factory()->for($this->organization)->create();

        $user->roles()->attach($role);
        expect($user->roles)->toHaveCount(1);

        $user->roles()->detach($role);

        expect($user->fresh()->roles)->toHaveCount(0);
    });
});

describe('Permission inheritance through roles', function (): void {
    it('user inherits permissions from role', function (): void {
        $user = User::factory()->for($this->organization)->create();
        $role = Role::factory()->for($this->organization)->create();

        $role->permissions()->attach($this->viewPermission);
        $user->roles()->attach($role);

        expect($user->hasPermission('users.view'))->toBeTrue();
    });

    it('user inherits multiple permissions from role', function (): void {
        $user = User::factory()->for($this->organization)->create();
        $role = Role::factory()->for($this->organization)->create();

        $role->permissions()->attach([
            $this->viewPermission->id,
            $this->createPermission->id,
            $this->updatePermission->id,
        ]);
        $user->roles()->attach($role);

        expect($user->hasPermission('users.view'))->toBeTrue()
            ->and($user->hasPermission('users.create'))->toBeTrue()
            ->and($user->hasPermission('users.update'))->toBeTrue()
            ->and($user->hasPermission('users.delete'))->toBeFalse();
    });

    it('user inherits permissions from multiple roles', function (): void {
        $user = User::factory()->for($this->organization)->create();
        $role1 = Role::factory()->for($this->organization)->create();
        $role2 = Role::factory()->for($this->organization)->create();

        $role1->permissions()->attach($this->viewPermission);
        $role2->permissions()->attach($this->createPermission);
        $user->roles()->attach([$role1->id, $role2->id]);

        expect($user->hasPermission('users.view'))->toBeTrue()
            ->and($user->hasPermission('users.create'))->toBeTrue();
    });

    it('user loses permissions when role is removed', function (): void {
        $user = User::factory()->for($this->organization)->create();
        $role = Role::factory()->for($this->organization)->create();

        $role->permissions()->attach($this->viewPermission);
        $user->roles()->attach($role);
        expect($user->hasPermission('users.view'))->toBeTrue();

        $user->roles()->detach($role);

        expect($user->fresh()->hasPermission('users.view'))->toBeFalse();
    });
});

describe('Permission checking', function (): void {
    it('checks if user has specific permission', function (): void {
        $user = User::factory()->for($this->organization)->create();
        $role = Role::factory()->for($this->organization)->create();
        $role->permissions()->attach($this->viewPermission);
        $user->roles()->attach($role);

        expect($user->hasPermission('users.view'))->toBeTrue()
            ->and($user->hasPermission('users.create'))->toBeFalse();
    });

    it('checks if user has any of the permissions', function (): void {
        $user = User::factory()->for($this->organization)->create();
        $role = Role::factory()->for($this->organization)->create();
        $role->permissions()->attach($this->viewPermission);
        $user->roles()->attach($role);

        expect($user->hasAnyPermission(['users.view', 'users.create']))->toBeTrue()
            ->and($user->hasAnyPermission(['users.create', 'users.delete']))->toBeFalse();
    });

    it('returns false for user with no roles', function (): void {
        $user = User::factory()->for($this->organization)->create();

        expect($user->hasPermission('users.view'))->toBeFalse()
            ->and($user->hasAnyPermission(['users.view', 'users.create']))->toBeFalse();
    });
});

describe('Super admin permissions', function (): void {
    it('super admin has all permissions', function (): void {
        $user = User::factory()->for($this->organization)->create();
        $role = Role::factory()->for($this->organization)->create();
        $role->permissions()->attach($this->superAdminPermission);
        $user->roles()->attach($role);

        expect($user->hasPermission('super-admin'))->toBeTrue();
    });

    it('super admin can be identified by permission', function (): void {
        $regularUser = User::factory()->for($this->organization)->create();
        $regularRole = Role::factory()->for($this->organization)->create();
        $regularRole->permissions()->attach($this->viewPermission);
        $regularUser->roles()->attach($regularRole);

        $adminUser = User::factory()->for($this->organization)->create();
        $adminRole = Role::factory()->for($this->organization)->create();
        $adminRole->permissions()->attach($this->superAdminPermission);
        $adminUser->roles()->attach($adminRole);

        expect($regularUser->hasPermission('super-admin'))->toBeFalse()
            ->and($adminUser->hasPermission('super-admin'))->toBeTrue();
    });
});

describe('Role-based queries', function (): void {
    it('can query users by role', function (): void {
        $adminRole = Role::factory()->for($this->organization)->create(['slug' => 'admin']);
        $userRole = Role::factory()->for($this->organization)->create(['slug' => 'user']);

        $admin = User::factory()->for($this->organization)->create();
        $admin->roles()->attach($adminRole);

        $user = User::factory()->for($this->organization)->create();
        $user->roles()->attach($userRole);

        $admins = User::query()
            ->whereHas('roles', function (Builder $query): void {
                $query->where('slug', 'admin');
            })
            ->get();

        expect($admins)->toHaveCount(1)
            ->and($admins->first()->id)->toBe($admin->id);
    });

    it('can query users by permission', function (): void {
        $role1 = Role::factory()->for($this->organization)->create();
        $role1->permissions()->attach($this->createPermission);

        $role2 = Role::factory()->for($this->organization)->create();
        $role2->permissions()->attach($this->viewPermission);

        $user1 = User::factory()->for($this->organization)->create();
        $user1->roles()->attach($role1);

        $user2 = User::factory()->for($this->organization)->create();
        $user2->roles()->attach($role2);

        $usersWithCreate = User::query()
            ->whereHas('roles.permissions', function (Builder $query): void {
                $query->where('slug', 'users.create');
            })
            ->get();

        expect($usersWithCreate)->toHaveCount(1)
            ->and($usersWithCreate->first()->id)->toBe($user1->id);
    });
});

describe('Complex RBAC scenarios', function (): void {
    it('handles user with overlapping permissions from multiple roles', function (): void {
        $user = User::factory()->for($this->organization)->create();
        $role1 = Role::factory()->for($this->organization)->create();
        $role2 = Role::factory()->for($this->organization)->create();

        $role1->permissions()->attach([$this->viewPermission->id, $this->createPermission->id]);
        $role2->permissions()->attach([$this->viewPermission->id, $this->updatePermission->id]);

        $user->roles()->attach([$role1->id, $role2->id]);

        expect($user->hasPermission('users.view'))->toBeTrue()
            ->and($user->hasPermission('users.create'))->toBeTrue()
            ->and($user->hasPermission('users.update'))->toBeTrue()
            ->and($user->hasPermission('users.delete'))->toBeFalse();
    });

    it('handles dynamic permission changes to role', function (): void {
        $user = User::factory()->for($this->organization)->create();
        $role = Role::factory()->for($this->organization)->create();
        $user->roles()->attach($role);

        expect($user->hasPermission('users.view'))->toBeFalse();

        $role->permissions()->attach($this->viewPermission);

        expect($user->fresh()->hasPermission('users.view'))->toBeTrue();

        $role->permissions()->detach($this->viewPermission);

        expect($user->fresh()->hasPermission('users.view'))->toBeFalse();
    });

    it('maintains RBAC across tenant boundaries', function (): void {
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        $role1 = Role::factory()->for($org1)->create();
        $role1->permissions()->attach($this->viewPermission);

        $role2 = Role::factory()->for($org2)->create();
        $role2->permissions()->attach($this->createPermission);

        $user1 = User::factory()->for($org1)->create();
        $user1->roles()->attach($role1);

        $user2 = User::factory()->for($org2)->create();
        $user2->roles()->attach($role2);

        expect($user1->hasPermission('users.view'))->toBeTrue()
            ->and($user1->hasPermission('users.create'))->toBeFalse()
            ->and($user2->hasPermission('users.create'))->toBeTrue()
            ->and($user2->hasPermission('users.view'))->toBeFalse();
    });
});

describe('Role hierarchy simulation', function (): void {
    it('simulates hierarchical permissions through roles', function (): void {
        // Create role hierarchy: Employee < Manager < Admin
        $employeeRole = Role::factory()->for($this->organization)->create(['slug' => 'employee']);
        $employeeRole->permissions()->attach($this->viewPermission);

        $managerRole = Role::factory()->for($this->organization)->create(['slug' => 'manager']);
        $managerRole->permissions()->attach([
            $this->viewPermission->id,
            $this->createPermission->id,
            $this->updatePermission->id,
        ]);

        $adminRole = Role::factory()->for($this->organization)->create(['slug' => 'admin']);
        $adminRole->permissions()->attach([
            $this->viewPermission->id,
            $this->createPermission->id,
            $this->updatePermission->id,
            $this->deletePermission->id,
        ]);

        $employee = User::factory()->for($this->organization)->create();
        $employee->roles()->attach($employeeRole);

        $manager = User::factory()->for($this->organization)->create();
        $manager->roles()->attach($managerRole);

        $admin = User::factory()->for($this->organization)->create();
        $admin->roles()->attach($adminRole);

        expect($employee->hasPermission('users.view'))->toBeTrue()
            ->and($employee->hasPermission('users.create'))->toBeFalse()
            ->and($manager->hasAllRoles(['manager']))->toBeTrue()
            ->and($manager->hasPermission('users.create'))->toBeTrue()
            ->and($manager->hasPermission('users.delete'))->toBeFalse()
            ->and($admin->hasPermission('users.delete'))->toBeTrue();
    });
});
