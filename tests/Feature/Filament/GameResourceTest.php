<?php

declare(strict_types=1);

use App\Enums\GameStatus;
use App\Filament\Management\Resources\Games\Pages\CreateGame;
use App\Filament\Management\Resources\Games\Pages\EditGame;
use App\Filament\Management\Resources\Games\Pages\ListGames;
use App\Models\Game;
use App\Models\Organization;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel('management');
    $this->organization = Organization::factory()->create();
    $this->user = User::factory()->create([
        'organization_id' => $this->organization->id,
        'email_verified_at' => now(),
    ]);
    $this->actingAs($this->user);
});

it('can render game list page', function (): void {
    Livewire::test(ListGames::class)
        ->assertSuccessful();
});

it('can list games', function (): void {
    $games = Game::factory()->count(3)->create([
        'organization_id' => $this->organization->id,
    ]);

    Livewire::test(ListGames::class)
        ->assertCanSeeTableRecords($games);
});

it('can search games by name', function (): void {
    $games = Game::factory()->count(5)->create([
        'organization_id' => $this->organization->id,
    ]);

    Livewire::test(ListGames::class)
        ->searchTable($games->first()->name)
        ->assertCanSeeTableRecords($games->take(1))
        ->assertCanNotSeeTableRecords($games->skip(1));
});

it('can filter games by status', function (): void {
    $activeGames = Game::factory()->count(2)->active()->create([
        'organization_id' => $this->organization->id,
    ]);
    $archivedGames = Game::factory()->count(2)->archived()->create([
        'organization_id' => $this->organization->id,
    ]);

    Livewire::test(ListGames::class)
        ->filterTable('status', [GameStatus::Active->value])
        ->assertCanSeeTableRecords($activeGames)
        ->assertCanNotSeeTableRecords($archivedGames);
});

it('can render create game page', function (): void {
    Livewire::test(CreateGame::class)
        ->assertSuccessful();
});

it('can create a game', function (): void {
    $gameData = [
        'organization_id' => $this->organization->id,
        'name' => 'Test Game',
        'slug' => 'test-game',
        'status' => GameStatus::Setup->value,
        'description' => 'A test game description',
    ];

    Livewire::test(CreateGame::class)
        ->fillForm($gameData)
        ->call('create')
        ->assertNotified()
        ->assertRedirect();

    $this->assertDatabaseHas(Game::class, [
        'name' => 'Test Game',
        'slug' => 'test-game',
        'organization_id' => $this->organization->id,
    ]);
});

it('validates required fields when creating a game', function (): void {
    Livewire::test(CreateGame::class)
        ->fillForm([
            'name' => '',
            'slug' => '',
        ])
        ->call('create')
        ->assertHasFormErrors(['name', 'slug']);
});

it('can render edit game page', function (): void {
    $game = Game::factory()->create([
        'organization_id' => $this->organization->id,
    ]);

    Livewire::test(EditGame::class, ['record' => $game->uuid])
        ->assertSuccessful();
});

it('can update a game', function (): void {
    $game = Game::factory()->create([
        'organization_id' => $this->organization->id,
    ]);

    $newData = [
        'name' => 'Updated Game Name',
        'status' => GameStatus::Active->value,
    ];

    Livewire::test(EditGame::class, ['record' => $game->uuid])
        ->fillForm($newData)
        ->call('save')
        ->assertNotified();

    expect($game->refresh())
        ->name->toBe('Updated Game Name')
        ->status->toBe(GameStatus::Active);
});

it('can delete a game', function (): void {
    $game = Game::factory()->create([
        'organization_id' => $this->organization->id,
    ]);

    Livewire::test(ListGames::class)
        ->callTableAction('delete', $game);

    $this->assertSoftDeleted($game);
});
