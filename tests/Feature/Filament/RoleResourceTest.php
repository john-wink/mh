<?php

declare(strict_types=1);

use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\Roles\Pages\ListRoles;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->organization = Organization::factory()->create();
    $this->user = User::factory()->create(['organization_id' => $this->organization->id]);
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
    $role = Role::factory()->create(['organization_id' => $this->organization->id]);
    $permissions = Permission::factory()->count(3)->create();

    Livewire::test(EditRole::class, ['record' => $role->getRouteKey()])
        ->fillForm([
            'permissions' => $permissions->pluck('id')->toArray(),
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($role->fresh()->permissions)->toHaveCount(3);
});

it('can delete role', function (): void {
    $role = Role::factory()->create(['organization_id' => $this->organization->id]);

    Livewire::test(ListRoles::class)
        ->callTableAction('delete', $role);

    $this->assertSoftDeleted($role);
});

it('can restore trashed roles', function (): void {
    $role = Role::factory()->create(['organization_id' => $this->organization->id]);
    $role->delete();

    Livewire::test(ListRoles::class)
        ->callTableBulkAction('restore', [$role]);

    expect($role->fresh()->trashed())->toBeFalse();
});
