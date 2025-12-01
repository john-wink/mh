<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->organization = Organization::factory()->create();
});

describe('Permission model relationships', function (): void {
    it('has roles relationship', function (): void {
        $permission = Permission::factory()->create();
        $role = Role::factory()->for($this->organization)->create();

        $permission->roles()->attach($role);

        expect($permission->roles)->toHaveCount(1)
            ->and($permission->roles->first()->id)->toBe($role->id);
    });

    it('can be attached to multiple roles', function (): void {
        $permission = Permission::factory()->create();
        $role1 = Role::factory()->for($this->organization)->create();
        $role2 = Role::factory()->for($this->organization)->create();

        $permission->roles()->attach([$role1->id, $role2->id]);

        expect($permission->roles)->toHaveCount(2);
    });

    it('can be attached to roles from different organizations', function (): void {
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();
        $permission = Permission::factory()->create();
        $role1 = Role::factory()->for($org1)->create();
        $role2 = Role::factory()->for($org2)->create();

        $permission->roles()->attach([$role1->id, $role2->id]);

        expect($permission->roles)->toHaveCount(2);
    });
});

describe('Permission validation rules', function (): void {
    it('provides correct create rules', function (): void {
        $rules = Permission::createRules();

        expect($rules)->toBeArray()
            ->and($rules)->toHaveKeys(['name', 'slug', 'description']);
    });

    it('provides correct update rules', function (): void {
        $rules = Permission::updateRules(1);

        expect($rules)->toBeArray()
            ->and($rules)->toHaveKeys(['name', 'slug', 'description']);
    });

    it('includes permission id in slug unique rule for updates', function (): void {
        $rules = Permission::updateRules(5);

        expect($rules['slug'])->toContain('unique:permissions,slug,5');
    });

    it('validates slug format', function (): void {
        $rules = Permission::createRules();

        expect($rules['slug'])->toContain('regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/');
    });

    it('requires unique slug on create', function (): void {
        $rules = Permission::createRules();

        expect($rules['slug'])->toContain('unique:permissions,slug');
    });

    it('allows optional description', function (): void {
        $rules = Permission::createRules();

        expect($rules['description'])->toContain('nullable');
    });
});

describe('Permission scopes', function (): void {
    it('can search by name', function (): void {
        $createPermission = Permission::factory()->create([
            'name' => 'Create Users',
            'slug' => 'users.create',
        ]);
        $deletePermission = Permission::factory()->create([
            'name' => 'Delete Users',
            'slug' => 'users.delete',
        ]);

        $permissions = Permission::search('Create')->get();

        expect($permissions)->toHaveCount(1)
            ->and($permissions->first()->id)->toBe($createPermission->id);
    });

    it('can search by description', function (): void {
        $createPermission = Permission::factory()->create([
            'name' => 'Create',
            'slug' => 'users.create',
            'description' => 'Allows creating new users in the system',
        ]);
        $deletePermission = Permission::factory()->create([
            'name' => 'Delete',
            'slug' => 'users.delete',
            'description' => 'Allows deleting users',
        ]);

        $permissions = Permission::search('creating')->get();

        expect($permissions)->toHaveCount(1)
            ->and($permissions->first()->id)->toBe($createPermission->id);
    });

    it('search is case insensitive', function (): void {
        $permission = Permission::factory()->create([
            'name' => 'Admin Access',
        ]);

        $permissions = Permission::search('ADMIN')->get();

        expect($permissions)->toHaveCount(1);
    });

    it('search returns multiple matches', function (): void {
        $createUserPermission = Permission::factory()->create([
            'name' => 'Create Users',
        ]);
        $updateUserPermission = Permission::factory()->create([
            'name' => 'Update Users',
        ]);
        $createPostPermission = Permission::factory()->create([
            'name' => 'Create Posts',
        ]);

        $permissions = Permission::search('User')->get();

        expect($permissions)->toHaveCount(2);
    });
});

describe('Permission model casts', function (): void {
    it('casts id to integer', function (): void {
        $permission = Permission::factory()->create();

        expect($permission->id)->toBeInt();
    });

    it('casts timestamps to datetime', function (): void {
        $permission = Permission::factory()->create();

        expect($permission->created_at)->toBeInstanceOf(CarbonInterface::class)
            ->and($permission->updated_at)->toBeInstanceOf(CarbonInterface::class);
    });

    it('casts strings correctly', function (): void {
        $permission = Permission::factory()->create([
            'name' => 'Test Permission',
            'slug' => 'test-permission',
            'description' => 'A test permission',
        ]);

        expect($permission->name)->toBeString()
            ->and($permission->slug)->toBeString()
            ->and($permission->description)->toBeString();
    });
});

describe('Permission soft deletes', function (): void {
    it('soft deletes permission', function (): void {
        $permission = Permission::factory()->create();

        $permission->delete();

        expect($permission->deleted_at)->not->toBeNull()
            ->and(Permission::query()->count())->toBe(0)
            ->and(Permission::withTrashed()->count())->toBe(1);
    });

    it('can restore soft deleted permission', function (): void {
        $permission = Permission::factory()->create();
        $permission->delete();

        $permission->restore();

        expect($permission->deleted_at)->toBeNull()
            ->and(Permission::query()->count())->toBe(1);
    });

    it('maintains relationships after soft delete', function (): void {
        $permission = Permission::factory()->create();
        $role = Role::factory()->for($this->organization)->create();
        $permission->roles()->attach($role);

        $permission->delete();

        expect(Permission::withTrashed()->find($permission->id)->roles)->toHaveCount(1);
    });
});
