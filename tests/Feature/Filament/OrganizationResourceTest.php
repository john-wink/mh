<?php

declare(strict_types=1);

use App\Filament\Resources\Organizations\Pages\CreateOrganization;
use App\Filament\Resources\Organizations\Pages\EditOrganization;
use App\Filament\Resources\Organizations\Pages\ListOrganizations;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('can render list page', function (): void {
    Livewire::test(ListOrganizations::class)
        ->assertSuccessful();
});

it('can list organizations', function (): void {
    $organizations = Organization::factory()->count(10)->create();

    Livewire::test(ListOrganizations::class)
        ->assertCanSeeTableRecords($organizations);
});

it('can search organizations by name', function (): void {
    $organizations = Organization::factory()->count(10)->create();
    $targetOrganization = $organizations->first();

    Livewire::test(ListOrganizations::class)
        ->searchTable($targetOrganization->name)
        ->assertCanSeeTableRecords([$targetOrganization])
        ->assertCanNotSeeTableRecords($organizations->skip(1));
});

it('can search organizations by slug', function (): void {
    $organizations = Organization::factory()->count(10)->create();
    $targetOrganization = $organizations->first();

    Livewire::test(ListOrganizations::class)
        ->searchTable($targetOrganization->slug)
        ->assertCanSeeTableRecords([$targetOrganization])
        ->assertCanNotSeeTableRecords($organizations->skip(1));
});

it('can sort organizations by name', function (): void {
    $organizations = Organization::factory()->count(3)->create();

    Livewire::test(ListOrganizations::class)
        ->sortTable('name')
        ->assertCanSeeTableRecords($organizations->sortBy('name'), inOrder: true)
        ->sortTable('name', 'desc')
        ->assertCanSeeTableRecords($organizations->sortByDesc('name'), inOrder: true);
});

it('can render create page', function (): void {
    Livewire::test(CreateOrganization::class)
        ->assertSuccessful();
});

it('can create organization', function (): void {
    $newData = Organization::factory()->make();

    Livewire::test(CreateOrganization::class)
        ->fillForm([
            'name' => $newData->name,
            'slug' => $newData->slug,
            'description' => $newData->description,
            'is_active' => $newData->is_active,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(Organization::class, [
        'name' => $newData->name,
        'slug' => $newData->slug,
        'description' => $newData->description,
        'is_active' => $newData->is_active,
    ]);
});

it('validates required fields on create', function (): void {
    Livewire::test(CreateOrganization::class)
        ->fillForm([
            'name' => '',
            'slug' => '',
        ])
        ->call('create')
        ->assertHasFormErrors([
            'name' => 'required',
            'slug' => 'required',
        ]);
});

it('validates slug format on create', function (): void {
    Livewire::test(CreateOrganization::class)
        ->fillForm([
            'name' => 'Test Org',
            'slug' => 'Invalid Slug!',
        ])
        ->call('create')
        ->assertHasFormErrors([
            'slug' => 'regex',
        ]);
});

it('validates unique slug on create', function (): void {
    $existingOrganization = Organization::factory()->create();

    Livewire::test(CreateOrganization::class)
        ->fillForm([
            'name' => 'Test Org',
            'slug' => $existingOrganization->slug,
        ])
        ->call('create')
        ->assertHasFormErrors([
            'slug' => 'unique',
        ]);
});

it('can render edit page', function (): void {
    $organization = Organization::factory()->create();

    Livewire::test(EditOrganization::class, ['record' => $organization->getRouteKey()])
        ->assertSuccessful();
});

it('can retrieve data on edit page', function (): void {
    $organization = Organization::factory()->create();

    Livewire::test(EditOrganization::class, ['record' => $organization->getRouteKey()])
        ->assertFormSet([
            'name' => $organization->name,
            'slug' => $organization->slug,
            'description' => $organization->description,
            'is_active' => $organization->is_active,
        ]);
});

it('can update organization', function (): void {
    $organization = Organization::factory()->create();
    $newData = Organization::factory()->make();

    Livewire::test(EditOrganization::class, ['record' => $organization->getRouteKey()])
        ->fillForm([
            'name' => $newData->name,
            'slug' => $newData->slug,
            'description' => $newData->description,
            'is_active' => $newData->is_active,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($organization->fresh())
        ->name->toBe($newData->name)
        ->slug->toBe($newData->slug)
        ->description->toBe($newData->description)
        ->is_active->toBe($newData->is_active);
});

it('can delete organization', function (): void {
    $organization = Organization::factory()->create();

    Livewire::test(ListOrganizations::class)
        ->callTableAction('delete', $organization);

    $this->assertSoftDeleted($organization);
});

it('can bulk delete organizations', function (): void {
    $organizations = Organization::factory()->count(10)->create();

    Livewire::test(ListOrganizations::class)
        ->callTableBulkAction('delete', $organizations);

    foreach ($organizations as $organization) {
        $this->assertSoftDeleted($organization);
    }
});

it('can filter trashed organizations', function (): void {
    $organization = Organization::factory()->create();
    $trashedOrganization = Organization::factory()->create();
    $trashedOrganization->delete();

    Livewire::test(ListOrganizations::class)
        ->filterTable('trashed', 'only')
        ->assertCanSeeTableRecords([$trashedOrganization])
        ->assertCanNotSeeTableRecords([$organization]);
});

it('can restore trashed organizations', function (): void {
    $organization = Organization::factory()->create();
    $organization->delete();

    Livewire::test(ListOrganizations::class)
        ->callTableBulkAction('restore', [$organization]);

    expect($organization->fresh()->trashed())->toBeFalse();
});
