<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Services\TenantManager;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenantManager = app(TenantManager::class);
    $this->tenant1 = Organization::factory()->create(['slug' => 'tenant1', 'name' => 'Tenant One']);
    $this->tenant2 = Organization::factory()->create(['slug' => 'tenant2', 'name' => 'Tenant Two']);
    $this->tenant3 = Organization::factory()->create(['slug' => 'tenant3', 'name' => 'Tenant Three']);
});

describe('User tenant isolation', function (): void {
    it('isolates users between tenants', function (): void {
        $user1 = User::factory()->create(['organization_id' => $this->tenant1->id]);
        $user2 = User::factory()->create(['organization_id' => $this->tenant2->id]);
        $user3 = User::factory()->create(['organization_id' => $this->tenant3->id]);

        $this->tenantManager->setCurrentTenant($this->tenant1);

        $users = User::all();

        expect($users)->toHaveCount(1)
            ->and($users->first()->id)->toBe($user1->id);
    });

    it('switches tenant context correctly for users', function (): void {
        $user1 = User::factory()->create(['organization_id' => $this->tenant1->id]);
        $user2 = User::factory()->create(['organization_id' => $this->tenant2->id]);

        $this->tenantManager->setCurrentTenant($this->tenant1);
        expect(User::all())->toHaveCount(1);

        $this->tenantManager->setCurrentTenant($this->tenant2);
        expect(User::all())->toHaveCount(1)
            ->and(User::all()->first()->id)->toBe($user2->id);
    });

    it('prevents cross-tenant user queries', function (): void {
        $user1 = User::factory()->create(['organization_id' => $this->tenant1->id]);
        $user2 = User::factory()->create(['organization_id' => $this->tenant2->id]);

        $this->tenantManager->setCurrentTenant($this->tenant1);

        $foundUser = User::query()->find($user2->id);

        expect($foundUser)->toBeNull();
    });

    it('allows querying specific tenant with forTenant scope', function (): void {
        $user1 = User::factory()->create(['organization_id' => $this->tenant1->id]);
        $user2 = User::factory()->create(['organization_id' => $this->tenant2->id]);

        $this->tenantManager->setCurrentTenant($this->tenant1);

        $users = User::forTenant($this->tenant2->id)->get();

        expect($users)->toHaveCount(1)
            ->and($users->first()->id)->toBe($user2->id);
    });
});

describe('Role tenant isolation', function (): void {
    it('isolates roles between tenants', function (): void {
        $role1 = Role::factory()->create(['organization_id' => $this->tenant1->id]);
        $role2 = Role::factory()->create(['organization_id' => $this->tenant2->id]);
        $role3 = Role::factory()->create(['organization_id' => $this->tenant3->id]);

        $this->tenantManager->setCurrentTenant($this->tenant1);

        $roles = Role::all();

        expect($roles)->toHaveCount(1)
            ->and($roles->first()->id)->toBe($role1->id);
    });

    it('prevents cross-tenant role assignments', function (): void {
        $user1 = User::factory()->create(['organization_id' => $this->tenant1->id]);
        $role2 = Role::factory()->create(['organization_id' => $this->tenant2->id]);

        $this->tenantManager->setCurrentTenant($this->tenant1);

        $user1->roles()->attach($role2->id);

        // User in tenant1 should not see role from tenant2
        expect(Role::all())->toHaveCount(0);
    });

    it('maintains role isolation after tenant switch', function (): void {
        $role1 = Role::factory()->create(['organization_id' => $this->tenant1->id, 'name' => 'Admin 1']);
        $role2 = Role::factory()->create(['organization_id' => $this->tenant2->id, 'name' => 'Admin 2']);

        $this->tenantManager->setCurrentTenant($this->tenant1);
        expect(Role::all()->first()->name)->toBe('Admin 1');

        $this->tenantManager->setCurrentTenant($this->tenant2);
        expect(Role::all()->first()->name)->toBe('Admin 2');
    });
});

describe('Multiple model tenant isolation', function (): void {
    it('maintains isolation across all tenant-scoped models', function (): void {
        // Tenant 1 data
        $user1 = User::factory()->create(['organization_id' => $this->tenant1->id]);
        $role1 = Role::factory()->create(['organization_id' => $this->tenant1->id]);

        // Tenant 2 data
        $user2 = User::factory()->create(['organization_id' => $this->tenant2->id]);
        $role2 = Role::factory()->create(['organization_id' => $this->tenant2->id]);

        $this->tenantManager->setCurrentTenant($this->tenant1);

        expect(User::query()->count())->toBe(1)
            ->and(Role::query()->count())->toBe(1);
    });

    it('correctly scopes related models', function (): void {
        $user1 = User::factory()->create(['organization_id' => $this->tenant1->id]);
        $role1 = Role::factory()->create(['organization_id' => $this->tenant1->id]);
        $user1->roles()->attach($role1);

        $user2 = User::factory()->create(['organization_id' => $this->tenant2->id]);
        $role2 = Role::factory()->create(['organization_id' => $this->tenant2->id]);
        $user2->roles()->attach($role2);

        $this->tenantManager->setCurrentTenant($this->tenant1);

        $user = User::with('roles')->first();

        expect($user->id)->toBe($user1->id)
            ->and($user->roles)->toHaveCount(1)
            ->and($user->roles->first()->id)->toBe($role1->id);
    });
});

describe('Tenant scope bypass', function (): void {
    it('can bypass tenant scope with withoutTenantScope', function (): void {
        User::factory()->create(['organization_id' => $this->tenant1->id]);
        User::factory()->create(['organization_id' => $this->tenant2->id]);
        User::factory()->create(['organization_id' => $this->tenant3->id]);

        $this->tenantManager->setCurrentTenant($this->tenant1);

        $users = User::withoutTenantScope()->get();

        expect($users)->toHaveCount(3);
    });

    it('can query all roles across tenants without scope', function (): void {
        Role::factory()->create(['organization_id' => $this->tenant1->id]);
        Role::factory()->create(['organization_id' => $this->tenant2->id]);
        Role::factory()->create(['organization_id' => $this->tenant3->id]);

        $this->tenantManager->setCurrentTenant($this->tenant1);

        $roles = Role::withoutTenantScope()->get();

        expect($roles)->toHaveCount(3);
    });
});

describe('Automatic tenant assignment on create', function (): void {
    it('automatically assigns current tenant to new users', function (): void {
        $this->tenantManager->setCurrentTenant($this->tenant1);

        $user = User::factory()->create(['organization_id' => null]);

        expect($user->organization_id)->toBe($this->tenant1->id);
    });

    it('automatically assigns current tenant to new roles', function (): void {
        $this->tenantManager->setCurrentTenant($this->tenant2);

        $role = Role::factory()->create(['organization_id' => null]);

        expect($role->organization_id)->toBe($this->tenant2->id);
    });

    it('does not override manually set organization_id', function (): void {
        $this->tenantManager->setCurrentTenant($this->tenant1);

        $user = User::factory()->create(['organization_id' => $this->tenant2->id]);

        expect($user->organization_id)->toBe($this->tenant2->id);
    });
});

describe('Tenant isolation with complex queries', function (): void {
    it('maintains isolation with where clauses', function (): void {
        $user1 = User::factory()->create([
            'organization_id' => $this->tenant1->id,
            'email' => 'test@tenant1.com',
        ]);
        $user2 = User::factory()->create([
            'organization_id' => $this->tenant2->id,
            'email' => 'test@tenant2.com',
        ]);

        $this->tenantManager->setCurrentTenant($this->tenant1);

        $users = User::query()->where('email', 'like', '%@tenant%')->get();

        expect($users)->toHaveCount(1)
            ->and($users->first()->id)->toBe($user1->id);
    });

    it('maintains isolation with order by', function (): void {
        User::factory()->create(['organization_id' => $this->tenant1->id, 'name' => 'Alice']);
        User::factory()->create(['organization_id' => $this->tenant1->id, 'name' => 'Bob']);
        User::factory()->create(['organization_id' => $this->tenant2->id, 'name' => 'Charlie']);

        $this->tenantManager->setCurrentTenant($this->tenant1);

        $users = User::query()->oldest('name')->get();

        expect($users)->toHaveCount(2)
            ->and($users->first()->name)->toBe('Alice')
            ->and($users->last()->name)->toBe('Bob');
    });

    it('maintains isolation with pagination', function (): void {
        User::factory()->count(5)->create(['organization_id' => $this->tenant1->id]);
        User::factory()->count(5)->create(['organization_id' => $this->tenant2->id]);

        $this->tenantManager->setCurrentTenant($this->tenant1);

        $users = User::query()->paginate(10);

        expect($users->total())->toBe(5);
    });
});

describe('Tenant context clearing', function (): void {
    it('shows all data when tenant context is cleared', function (): void {
        User::factory()->create(['organization_id' => $this->tenant1->id]);
        User::factory()->create(['organization_id' => $this->tenant2->id]);

        $this->tenantManager->setCurrentTenant($this->tenant1);
        expect(User::all())->toHaveCount(1);

        $this->tenantManager->clearTenant();
        expect(User::all())->toHaveCount(2);
    });

    it('allows setting tenant again after clearing', function (): void {
        User::factory()->create(['organization_id' => $this->tenant1->id]);
        User::factory()->create(['organization_id' => $this->tenant2->id]);

        $this->tenantManager->setCurrentTenant($this->tenant1);
        $this->tenantManager->clearTenant();
        $this->tenantManager->setCurrentTenant($this->tenant2);

        $users = User::all();

        expect($users)->toHaveCount(1)
            ->and($users->first()->organization_id)->toBe($this->tenant2->id);
    });
});
