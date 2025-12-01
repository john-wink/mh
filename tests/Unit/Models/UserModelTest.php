<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->organization = Organization::factory()->create();
});

describe('User model relationships', function (): void {
    it('has roles relationship', function (): void {
        $user = User::factory()->for($this->organization)->create();
        $role = Role::factory()->for($this->organization)->create();

        $user->roles()->attach($role);

        expect($user->roles)->toHaveCount(1)
            ->and($user->roles->first()->id)->toBe($role->id);
    });

    it('can have multiple roles', function (): void {
        $user = User::factory()->for($this->organization)->create();
        $role1 = Role::factory()->for($this->organization)->create();
        $role2 = Role::factory()->for($this->organization)->create();

        $user->roles()->attach([$role1->id, $role2->id]);

        expect($user->roles)->toHaveCount(2);
    });
});

describe('User hasRole method', function (): void {
    it('returns true when user has the role', function (): void {
        $user = User::factory()->for($this->organization)->create();
        $role = Role::factory()->for($this->organization)->create(['slug' => 'admin']);

        $user->roles()->attach($role);

        expect($user->hasRole('admin'))->toBeTrue();
    });

    it('returns false when user does not have the role', function (): void {
        $user = User::factory()->for($this->organization)->create();

        expect($user->hasRole('admin'))->toBeFalse();
    });

    it('handles multiple roles correctly', function (): void {
        $user = User::factory()->for($this->organization)->create();
        $adminRole = Role::factory()->for($this->organization)->create(['slug' => 'admin']);
        $editorRole = Role::factory()->for($this->organization)->create(['slug' => 'editor']);

        $user->roles()->attach($adminRole);

        expect($user->hasRole('admin'))->toBeTrue()
            ->and($user->hasRole('editor'))->toBeFalse();
    });
});

describe('User hasAnyRole method', function (): void {
    it('returns true when user has at least one of the roles', function (): void {
        $user = User::factory()->for($this->organization)->create();
        $adminRole = Role::factory()->for($this->organization)->create(['slug' => 'admin']);

        $user->roles()->attach($adminRole);

        expect($user->hasAnyRole(['admin', 'editor']))->toBeTrue();
    });

    it('returns false when user has none of the roles', function (): void {
        $user = User::factory()->for($this->organization)->create();

        expect($user->hasAnyRole(['admin', 'editor']))->toBeFalse();
    });

    it('returns true when user has all of the roles', function (): void {
        $user = User::factory()->for($this->organization)->create();
        $adminRole = Role::factory()->for($this->organization)->create(['slug' => 'admin']);
        $editorRole = Role::factory()->for($this->organization)->create(['slug' => 'editor']);

        $user->roles()->attach([$adminRole->id, $editorRole->id]);

        expect($user->hasAnyRole(['admin', 'editor']))->toBeTrue();
    });
});

describe('User hasAllRoles method', function (): void {
    it('returns true when user has all specified roles', function (): void {
        $user = User::factory()->for($this->organization)->create();
        $adminRole = Role::factory()->for($this->organization)->create(['slug' => 'admin']);
        $editorRole = Role::factory()->for($this->organization)->create(['slug' => 'editor']);

        $user->roles()->attach([$adminRole->id, $editorRole->id]);

        expect($user->hasAllRoles(['admin', 'editor']))->toBeTrue();
    });

    it('returns false when user has only some of the roles', function (): void {
        $user = User::factory()->for($this->organization)->create();
        $adminRole = Role::factory()->for($this->organization)->create(['slug' => 'admin']);

        $user->roles()->attach($adminRole);

        expect($user->hasAllRoles(['admin', 'editor']))->toBeFalse();
    });

    it('returns false when user has none of the roles', function (): void {
        $user = User::factory()->for($this->organization)->create();

        expect($user->hasAllRoles(['admin', 'editor']))->toBeFalse();
    });

    it('returns true for empty array', function (): void {
        $user = User::factory()->for($this->organization)->create();

        expect($user->hasAllRoles([]))->toBeTrue();
    });
});

describe('User hasPermission method', function (): void {
    it('returns true when user has permission through role', function (): void {
        $user = User::factory()->for($this->organization)->create();
        $permission = Permission::factory()->create(['slug' => 'users.create']);
        $role = Role::factory()->for($this->organization)->create();

        $role->permissions()->attach($permission);
        $user->roles()->attach($role);

        expect($user->hasPermission('users.create'))->toBeTrue();
    });

    it('returns false when user does not have the permission', function (): void {
        $user = User::factory()->for($this->organization)->create();
        $role = Role::factory()->for($this->organization)->create();

        $user->roles()->attach($role);

        expect($user->hasPermission('users.create'))->toBeFalse();
    });

    it('returns true when user has permission through any role', function (): void {
        $user = User::factory()->for($this->organization)->create();
        $permission = Permission::factory()->create(['slug' => 'users.create']);
        $role1 = Role::factory()->for($this->organization)->create();
        $role2 = Role::factory()->for($this->organization)->create();

        $role2->permissions()->attach($permission);
        $user->roles()->attach([$role1->id, $role2->id]);

        expect($user->hasPermission('users.create'))->toBeTrue();
    });
});

describe('User hasAnyPermission method', function (): void {
    it('returns true when user has at least one permission', function (): void {
        $user = User::factory()->for($this->organization)->create();
        $createPermission = Permission::factory()->create(['slug' => 'users.create']);
        $role = Role::factory()->for($this->organization)->create();

        $role->permissions()->attach($createPermission);
        $user->roles()->attach($role);

        expect($user->hasAnyPermission(['users.create', 'users.delete']))->toBeTrue();
    });

    it('returns false when user has none of the permissions', function (): void {
        $user = User::factory()->for($this->organization)->create();
        $role = Role::factory()->for($this->organization)->create();

        $user->roles()->attach($role);

        expect($user->hasAnyPermission(['users.create', 'users.delete']))->toBeFalse();
    });

    it('returns true when user has all permissions', function (): void {
        $user = User::factory()->for($this->organization)->create();
        $createPermission = Permission::factory()->create(['slug' => 'users.create']);
        $deletePermission = Permission::factory()->create(['slug' => 'users.delete']);
        $role = Role::factory()->for($this->organization)->create();

        $role->permissions()->attach([$createPermission->id, $deletePermission->id]);
        $user->roles()->attach($role);

        expect($user->hasAnyPermission(['users.create', 'users.delete']))->toBeTrue();
    });
});

describe('User validation rules', function (): void {
    it('provides correct create rules', function (): void {
        $rules = User::createRules();

        expect($rules)->toBeArray()
            ->and($rules)->toHaveKeys(['organization_id', 'name', 'email', 'password']);
    });

    it('provides correct update rules', function (): void {
        $rules = User::updateRules(1);

        expect($rules)->toBeArray()
            ->and($rules)->toHaveKeys(['organization_id', 'name', 'email', 'password']);
    });

    it('includes user id in email unique rule for updates', function (): void {
        $rules = User::updateRules(5);

        expect($rules['email'])->toContain('unique:users,email,5');
    });
});

describe('User scopes', function (): void {
    it('can filter by organization', function (): void {
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        $user1 = User::factory()->for($org1)->create();
        $user2 = User::factory()->for($org2)->create();

        $users = User::forOrganization($org1->id)->get();

        expect($users)->toHaveCount(1)
            ->and($users->first()->id)->toBe($user1->id);
    });

    it('can filter verified users', function (): void {
        $verifiedUser = User::factory()->for($this->organization)->create([
            'email_verified_at' => now(),
        ]);
        $unverifiedUser = User::factory()->for($this->organization)->create([
            'email_verified_at' => null,
        ]);

        $users = User::verified()->get();

        expect($users)->toHaveCount(1)
            ->and($users->first()->id)->toBe($verifiedUser->id);
    });

    it('can filter unverified users', function (): void {
        $verifiedUser = User::factory()->for($this->organization)->create([
            'email_verified_at' => now(),
        ]);
        $unverifiedUser = User::factory()->for($this->organization)->create([
            'email_verified_at' => null,
        ]);

        $users = User::unverified()->get();

        expect($users)->toHaveCount(1)
            ->and($users->first()->id)->toBe($unverifiedUser->id);
    });
});

describe('User model casts', function (): void {
    it('casts email_verified_at to datetime', function (): void {
        $user = User::factory()->for($this->organization)->create([
            'email_verified_at' => '2024-01-01 12:00:00',
        ]);

        expect($user->email_verified_at)->toBeInstanceOf(CarbonInterface::class);
    });

    it('hashes password', function (): void {
        $user = User::factory()->for($this->organization)->create([
            'password' => 'plain-password',
        ]);

        expect($user->password)->not->toBe('plain-password')
            ->and(mb_strlen($user->password))->toBeGreaterThan(10);
    });
});
