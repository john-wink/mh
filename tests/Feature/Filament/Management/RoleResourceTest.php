<?php

declare(strict_types=1);

use App\Filament\Management\Resources\Roles\Pages\CreateRole;
use App\Filament\Management\Resources\Roles\Pages\EditRole;
use App\Filament\Management\Resources\Roles\Pages\ListRoles;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->organization = Organization::factory()->create();

    // Create super admin permission
    $superAdminPermission = Permission::factory()->create([
        'slug' => 'super-admin',
        'name' => 'Super Admin',
    ]);

    // Create admin role with permission
    $adminRole = Role::factory()->create([
        'name' => 'Super Admin',
        'slug' => 'super-admin',
    ]);
    $adminRole->permissions()->attach($superAdminPermission);

    // Create user with admin role
    $this->user = User::factory()->create(['organization_id' => $this->organization->id]);
    $this->user->roles()->attach($adminRole);

    $this->actingAs($this->user);
});

it('can render list page', function (): void {
    Livewire::test(ListRoles::class)
        ->assertSuccessful();
});

it('can list roles', function (): void {
    $roles = Role::factory()->count(10)->create(['organization_id' => $this->organization->id]);

    Livewire::test(ListRoles::class)
        ->assertCanSeeTableRecords($roles);
});

it('can create role', function (): void {
    $newData = Role::factory()->make(['organization_id' => $this->organization->id]);

    Livewire::test(CreateRole::class)
        ->fillForm([
            'organization_id' => $newData->organization_id,
            'name' => $newData->name,
            'slug' => $newData->slug,
            'description' => $newData->description,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(Role::class, [
        'organization_id' => $newData->organization_id,
        'name' => $newData->name,
        'slug' => $newData->slug,
    ]);
});

it('validates required fields on create', function (): void {
    Livewire::test(CreateRole::class)
        ->fillForm([
            'name' => '',
            'slug' => '',
        ])
        ->call('create')
        ->assertHasFormErrors([
            'organization_id' => 'required',
            'name' => 'required',
            'slug' => 'required',
        ]);
});

it('can assign permissions to role', function (): void {
    [$role, $permissions] = DB::transaction(function (): array {

        $role = Role::factory()->create(['organization_id' => $this->organization->id]);
        $permissions = Permission::factory()->count(3)->create();

        return [$role, $permissions];
    }, 100);

    Livewire::test(EditRole::class, ['record' => $role->getRouteKey()])
        ->fillForm([
            'permissions' => $permissions->pluck('id')->toArray(),
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($role->fresh()->permissions)->toHaveCount(3);
});

it('can delete role', function (): void {
    $role = DB::transaction(function () {
        return Role::factory()->create(['organization_id' => $this->organization->id]);
    }, 3);

    Livewire::test(ListRoles::class)
        ->assertActionVisible(TestAction::make(DeleteAction::getDefaultName())->table($role))
        ->callAction(TestAction::make(DeleteAction::getDefaultName())->table($role));

    $this->assertSoftDeleted($role);
});

it('can restore trashed roles', function (): void {
    $role = Role::factory()->create(['organization_id' => $this->organization->id]);
    $role->delete();

    Livewire::test(ListRoles::class)
        ->filterTable(TrashedFilter::getDefaultName(), false)
        ->loadTable()
        ->assertCanSeeTableRecords([$role])
        ->callTableBulkAction('restore', [$role]);

    expect($role->fresh()->trashed())->toBeFalse();
});
