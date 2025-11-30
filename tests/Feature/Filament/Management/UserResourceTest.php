<?php

declare(strict_types=1);

use App\Filament\Management\Resources\Users\Pages\CreateUser;
use App\Filament\Management\Resources\Users\Pages\EditUser;
use App\Filament\Management\Resources\Users\Pages\ListUsers;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
    Livewire::test(ListUsers::class)
        ->assertSuccessful();
});

it('can list users', function (): void {
    $users = User::factory()->count(10)->create(['organization_id' => $this->organization->id]);

    Livewire::test(ListUsers::class)
        ->assertCanSeeTableRecords($users);
});

it('can search users by name', function (): void {
    $users = User::factory()->count(10)->create(['organization_id' => $this->organization->id]);
    $targetUser = $users->first();

    Livewire::test(ListUsers::class)
        ->searchTable($targetUser->name)
        ->assertCanSeeTableRecords([$targetUser])
        ->assertCanNotSeeTableRecords($users->skip(1));
});

it('can search users by email', function (): void {
    $users = User::factory()->count(10)->create(['organization_id' => $this->organization->id]);
    $targetUser = $users->first();

    Livewire::test(ListUsers::class)
        ->searchTable($targetUser->email)
        ->assertCanSeeTableRecords([$targetUser])
        ->assertCanNotSeeTableRecords($users->skip(1));
});

it('can sort users by name', function (): void {
    $users = User::factory()->count(3)->create(['organization_id' => $this->organization->id]);

    Livewire::test(ListUsers::class)
        ->sortTable('name')
        ->assertCanSeeTableRecords($users->sortBy('name'), inOrder: true)
        ->sortTable('name', 'desc')
        ->assertCanSeeTableRecords($users->sortByDesc('name'), inOrder: true);
});

it('can render create page', function (): void {
    Livewire::test(CreateUser::class)
        ->assertSuccessful();
});

it('can create user', function (): void {
    $newData = User::factory()->make(['organization_id' => $this->organization->id]);

    Livewire::test(CreateUser::class)
        ->fillForm([
            'organization_id' => $newData->organization_id,
            'name' => $newData->name,
            'email' => $newData->email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(User::class, [
        'organization_id' => $newData->organization_id,
        'name' => $newData->name,
        'email' => $newData->email,
    ]);
});

it('validates required fields on create', function (): void {
    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => '',
            'email' => '',
            'password' => '',
        ])
        ->call('create')
        ->assertHasFormErrors([
            'organization_id' => 'required',
            'name' => 'required',
            'email' => 'required',
            'password' => 'required',
        ]);
});

it('validates email format on create', function (): void {
    Livewire::test(CreateUser::class)
        ->fillForm([
            'organization_id' => $this->organization->id,
            'name' => 'Test User',
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
        ->call('create')
        ->assertHasFormErrors([
            'email' => 'email',
        ]);
});

it('validates unique email on create', function (): void {
    $existingUser = User::factory()->create();

    Livewire::test(CreateUser::class)
        ->fillForm([
            'organization_id' => $this->organization->id,
            'name' => 'Test User',
            'email' => $existingUser->email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
        ->call('create')
        ->assertHasFormErrors([
            'email' => 'unique',
        ]);
});

it('validates password confirmation on create', function (): void {
    Livewire::test(CreateUser::class)
        ->fillForm([
            'organization_id' => $this->organization->id,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different-password',
        ])
        ->call('create')
        ->assertHasFormErrors([
            'password' => 'confirmed',
        ]);
});

it('can render edit page', function (): void {
    $user = User::factory()->create(['organization_id' => $this->organization->id]);

    Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
        ->assertSuccessful();
});

it('can retrieve data on edit page', function (): void {
    $user = User::factory()->create(['organization_id' => $this->organization->id]);

    Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
        ->assertFormSet([
            'organization_id' => $user->organization_id,
            'name' => $user->name,
            'email' => $user->email,
        ]);
});

it('can update user', function (): void {
    $user = User::factory()->create(['organization_id' => $this->organization->id]);
    $newData = User::factory()->make(['organization_id' => $this->organization->id]);

    Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
        ->fillForm([
            'organization_id' => $newData->organization_id,
            'name' => $newData->name,
            'email' => $newData->email,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($user->fresh())
        ->organization_id->toBe($newData->organization_id)
        ->name->toBe($newData->name)
        ->email->toBe($newData->email);
});

it('can assign roles to user', function (): void {
    $user = User::factory()->create(['organization_id' => $this->organization->id]);
    $roles = Role::factory()->count(3)->create(['organization_id' => $this->organization->id]);

    Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
        ->fillForm([
            'roles' => $roles->pluck('id')->toArray(),
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($user->fresh()->roles)->toHaveCount(3);
});

it('can delete user', function (): void {
    $user = User::factory()->create(['organization_id' => $this->organization->id]);

    Livewire::test(ListUsers::class)
        ->callTableAction('delete', $user);

    $this->assertSoftDeleted($user);
});

it('can bulk delete users', function (): void {
    $users = User::factory()->count(10)->create(['organization_id' => $this->organization->id]);

    Livewire::test(ListUsers::class)
        ->callTableBulkAction('delete', $users);

    foreach ($users as $user) {
        $this->assertSoftDeleted($user);
    }
});

it('can filter trashed users', function (): void {
    $user = User::factory()->create(['organization_id' => $this->organization->id]);
    $trashedUser = User::factory()->create(['organization_id' => $this->organization->id]);
    $trashedUser->delete();

    Livewire::test(ListUsers::class)
        ->filterTable(TrashedFilter::getDefaultName(), false)
        ->loadTable()
        ->assertCanSeeTableRecords([$trashedUser])
        ->assertCanNotSeeTableRecords([$user]);
});

it('can restore trashed users', function (): void {
    $user = User::factory()->create(['organization_id' => $this->organization->id]);
    $user->delete();

    Livewire::test(ListUsers::class)
        ->filterTable(TrashedFilter::getDefaultName(), false)
        ->loadTable()
        ->assertCanSeeTableRecords([$user])
        ->callAction(RestoreBulkAction::getDefaultName(), [$user]);

    expect($user->fresh()->trashed())->toBeFalse();
});
