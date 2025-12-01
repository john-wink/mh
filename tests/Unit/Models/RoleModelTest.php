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

describe('Role model relationships', function (): void {
    it('has users relationship', function (): void {
        $role = Role::factory()->for($this->organization)->create();
        $user = User::factory()->for($this->organization)->create();

        $role->users()->attach($user);

        expect($role->users)->toHaveCount(1)
            ->and($role->users->first()->id)->toBe($user->id);
    });

    it('has permissions relationship', function (): void {
        $role = Role::factory()->for($this->organization)->create();
        $permission = Permission::factory()->create();

        $role->permissions()->attach($permission);

        expect($role->permissions)->toHaveCount(1)
            ->and($role->permissions->first()->id)->toBe($permission->id);
    });

    it('can have multiple users', function (): void {
        $role = Role::factory()->for($this->organization)->create();
        $user1 = User::factory()->for($this->organization)->create();
        $user2 = User::factory()->for($this->organization)->create();

        $role->users()->attach([$user1->id, $user2->id]);

        expect($role->users)->toHaveCount(2);
    });

    it('can have multiple permissions', function (): void {
        $role = Role::factory()->for($this->organization)->create();
        $permission1 = Permission::factory()->create();
        $permission2 = Permission::factory()->create();

        $role->permissions()->attach([$permission1->id, $permission2->id]);

        expect($role->permissions)->toHaveCount(2);
    });
});

describe('Role hasPermission method', function (): void {
    it('returns true when role has the permission', function (): void {
        $role = Role::factory()->for($this->organization)->create();
        $permission = Permission::factory()->create(['slug' => 'users.create']);

        $role->permissions()->attach($permission);

        expect($role->hasPermission('users.create'))->toBeTrue();
    });

    it('returns false when role does not have the permission', function (): void {
        $role = Role::factory()->for($this->organization)->create();

        expect($role->hasPermission('users.create'))->toBeFalse();
    });

    it('handles multiple permissions correctly', function (): void {
        $role = Role::factory()->for($this->organization)->create();
        $createPermission = Permission::factory()->create(['slug' => 'users.create']);
        $deletePermission = Permission::factory()->create(['slug' => 'users.delete']);

        $role->permissions()->attach($createPermission);

        expect($role->hasPermission('users.create'))->toBeTrue()
            ->and($role->hasPermission('users.delete'))->toBeFalse();
    });
});

describe('Role givePermission method', function (): void {
    it('attaches permission to role', function (): void {
        $role = Role::factory()->for($this->organization)->create();
        $permission = Permission::factory()->create();

        $role->givePermission($permission);

        expect($role->permissions)->toHaveCount(1)
            ->and($role->hasPermission($permission->slug))->toBeTrue();
    });

    it('does not attach duplicate permissions', function (): void {
        $role = Role::factory()->for($this->organization)->create();
        $permission = Permission::factory()->create();

        $role->givePermission($permission);
        $role->givePermission($permission);

        expect($role->fresh()->permissions)->toHaveCount(1);
    });

    it('can give multiple different permissions', function (): void {
        $role = Role::factory()->for($this->organization)->create();
        $permission1 = Permission::factory()->create(['slug' => 'test-permission-1']);
        $permission2 = Permission::factory()->create(['slug' => 'test-permission-2']);

        $role->givePermission($permission1);
        $role->givePermission($permission2);

        expect($role->fresh()->permissions)->toHaveCount(2);
    });
});

describe('Role revokePermission method', function (): void {
    it('detaches permission from role', function (): void {
        $role = Role::factory()->for($this->organization)->create();
        $permission = Permission::factory()->create();

        $role->permissions()->attach($permission);
        expect($role->permissions)->toHaveCount(1);

        $role->revokePermission($permission);

        expect($role->fresh()->permissions)->toHaveCount(0);
    });

    it('handles revoking non-existent permission gracefully', function (): void {
        $role = Role::factory()->for($this->organization)->create();
        $permission = Permission::factory()->create();

        $role->revokePermission($permission);

        expect($role->permissions)->toHaveCount(0);
    });

    it('only revokes specified permission', function (): void {
        $role = Role::factory()->for($this->organization)->create();
        $permission1 = Permission::factory()->create();
        $permission2 = Permission::factory()->create();

        $role->permissions()->attach([$permission1->id, $permission2->id]);

        $role->revokePermission($permission1);

        expect($role->fresh()->permissions)->toHaveCount(1)
            ->and($role->hasPermission($permission2->slug))->toBeTrue()
            ->and($role->hasPermission($permission1->slug))->toBeFalse();
    });
});

describe('Role validation rules', function (): void {
    it('provides correct create rules', function (): void {
        $rules = Role::createRules();

        expect($rules)->toBeArray()
            ->and($rules)->toHaveKeys(['organization_id', 'name', 'slug', 'description']);
    });

    it('provides correct update rules', function (): void {
        $rules = Role::updateRules(1);

        expect($rules)->toBeArray()
            ->and($rules)->toHaveKeys(['organization_id', 'name', 'slug', 'description']);
    });

    it('validates slug format', function (): void {
        $rules = Role::createRules();

        expect($rules['slug'])->toContain('regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/');
    });
});

describe('Role scopes', function (): void {
    it('can filter by organization', function (): void {
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        $role1 = Role::factory()->for($org1)->create();
        $role2 = Role::factory()->for($org2)->create();

        $roles = Role::forOrganization($org1->id)->get();

        expect($roles)->toHaveCount(1)
            ->and($roles->first()->id)->toBe($role1->id);
    });

    it('can search by name', function (): void {
        $adminRole = Role::factory()->for($this->organization)->create([
            'name' => 'Administrator',
        ]);
        $editorRole = Role::factory()->for($this->organization)->create([
            'name' => 'Editor',
        ]);

        $roles = Role::search('Admin')->get();

        expect($roles)->toHaveCount(1)
            ->and($roles->first()->id)->toBe($adminRole->id);
    });

    it('can search by description', function (): void {
        $adminRole = Role::factory()->for($this->organization)->create([
            'name' => 'Admin',
            'description' => 'System administrator with full access',
        ]);
        $editorRole = Role::factory()->for($this->organization)->create([
            'name' => 'Editor',
            'description' => 'Can edit content',
        ]);

        $roles = Role::search('administrator')->get();

        expect($roles)->toHaveCount(1)
            ->and($roles->first()->id)->toBe($adminRole->id);
    });

    it('search is case insensitive', function (): void {
        $role = Role::factory()->for($this->organization)->create([
            'name' => 'Administrator',
        ]);

        $roles = Role::search('ADMIN')->get();

        expect($roles)->toHaveCount(1);
    });
});

describe('Role model casts', function (): void {
    it('casts organization_id to integer', function (): void {
        $role = Role::factory()->for($this->organization)->create();

        expect($role->organization_id)->toBeInt();
    });

    it('casts timestamps to datetime', function (): void {
        $role = Role::factory()->for($this->organization)->create();

        expect($role->created_at)->toBeInstanceOf(CarbonInterface::class)
            ->and($role->updated_at)->toBeInstanceOf(CarbonInterface::class);
    });
});
