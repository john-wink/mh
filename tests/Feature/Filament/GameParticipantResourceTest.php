<?php

declare(strict_types=1);

use App\Enums\ParticipantRole;
use App\Filament\Management\Resources\GameParticipants\Pages\CreateGameParticipant;
use App\Filament\Management\Resources\GameParticipants\Pages\EditGameParticipant;
use App\Filament\Management\Resources\GameParticipants\Pages\ListGameParticipants;
use App\Models\Game;
use App\Models\GameParticipant;
use App\Models\Organization;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\QueryException;
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

it('can render game participant list page', function (): void {
    Livewire::test(ListGameParticipants::class)
        ->assertSuccessful();
});

it('can list game participants', function (): void {
    $game = Game::factory()->create(['organization_id' => $this->organization->id]);
    $participants = GameParticipant::factory()->count(3)->create([
        'game_id' => $game->id,
    ]);

    Livewire::test(ListGameParticipants::class)
        ->assertCanSeeTableRecords($participants);
});

it('can filter participants by role', function (): void {
    $game = Game::factory()->create(['organization_id' => $this->organization->id]);
    $runners = GameParticipant::factory()->count(2)->runner()->create(['game_id' => $game->id]);
    $hunters = GameParticipant::factory()->count(2)->hunter()->create(['game_id' => $game->id]);

    Livewire::test(ListGameParticipants::class)
        ->filterTable('role', [ParticipantRole::Runner->value])
        ->assertCanSeeTableRecords($runners)
        ->assertCanNotSeeTableRecords($hunters);
});

it('can render create participant page', function (): void {
    Livewire::test(CreateGameParticipant::class)
        ->assertSuccessful();
});

it('can create a game participant', function (): void {
    $game = Game::factory()->create(['organization_id' => $this->organization->id]);
    $user = User::factory()->create(['organization_id' => $this->organization->id]);

    $participantData = [
        'game_id' => $game->id,
        'user_id' => $user->id,
        'role' => ParticipantRole::Runner->value,
        'participant_number' => 42,
    ];

    Livewire::test(CreateGameParticipant::class)
        ->fillForm($participantData)
        ->call('create')
        ->assertNotified()
        ->assertRedirect();

    $this->assertDatabaseHas(GameParticipant::class, [
        'game_id' => $game->id,
        'user_id' => $user->id,
        'role' => ParticipantRole::Runner->value,
        'participant_number' => 42,
    ]);
});

it('validates required fields when creating a participant', function (): void {
    Livewire::test(CreateGameParticipant::class)
        ->fillForm([
            'game_id' => null,
            'user_id' => null,
            'role' => null,
        ])
        ->call('create')
        ->assertHasFormErrors(['game_id', 'user_id', 'role']);
});

it('can render edit participant page', function (): void {
    $participant = GameParticipant::factory()->create();

    Livewire::test(EditGameParticipant::class, ['record' => $participant->uuid])
        ->assertSuccessful();
});

it('can update a game participant', function (): void {
    $participant = GameParticipant::factory()->runner()->create();

    $newData = [
        'role' => ParticipantRole::Hunter->value,
        'participant_number' => 99,
    ];

    Livewire::test(EditGameParticipant::class, ['record' => $participant->uuid])
        ->fillForm($newData)
        ->call('save')
        ->assertNotified();

    expect($participant->refresh())
        ->role->toBe(ParticipantRole::Hunter)
        ->participant_number->toBe(99);
});

it('can delete a game participant', function (): void {
    $participant = GameParticipant::factory()->create();

    Livewire::test(ListGameParticipants::class)
        ->callTableAction('delete', $participant);

    $this->assertSoftDeleted($participant);
});

it('prevents duplicate participant entries for same user in same game', function (): void {
    $game = Game::factory()->create(['organization_id' => $this->organization->id]);
    $user = User::factory()->create(['organization_id' => $this->organization->id]);

    GameParticipant::factory()->create([
        'game_id' => $game->id,
        'user_id' => $user->id,
    ]);

    expect(fn () => GameParticipant::factory()->create([
        'game_id' => $game->id,
        'user_id' => $user->id,
    ]))->toThrow(QueryException::class);
});
