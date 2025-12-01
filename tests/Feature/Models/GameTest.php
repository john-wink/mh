<?php

declare(strict_types=1);

use App\Enums\GamePhase;
use App\Models\Game;
use App\Models\GameEvent;
use App\Models\GameStateTransition;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create a game', function (): void {
    $organization = Organization::factory()->create();
    $game = Game::factory()->create([
        'organization_id' => $organization->id,
        'name' => 'Test Game',
    ]);

    expect($game->name)->toBe('Test Game')
        ->and($game->organization_id)->toBe($organization->id)
        ->and($game->current_phase)->toBe(GamePhase::Setup);
});

it('has default phase of setup', function (): void {
    $game = Game::factory()->create();

    expect($game->current_phase)->toBe(GamePhase::Setup);
});

it('can soft delete a game', function (): void {
    $game = Game::factory()->create();

    $game->delete();

    expect($game->trashed())->toBeTrue()
        ->and(Game::query()->withTrashed()->find($game->id))->not->toBeNull();
});

it('belongs to an organization', function (): void {
    $organization = Organization::factory()->create();
    $game = Game::factory()->create(['organization_id' => $organization->id]);

    expect($game->organization->id)->toBe($organization->id);
});

it('has many state transitions', function (): void {
    $game = Game::factory()->create();
    GameStateTransition::factory()->count(3)->create(['game_id' => $game->id]);

    expect($game->stateTransitions)->toHaveCount(3);
});

it('has many events', function (): void {
    $game = Game::factory()->create();
    GameEvent::factory()->count(5)->create(['game_id' => $game->id]);

    expect($game->events)->toHaveCount(5);
});

it('can transition to pre-game from setup', function (): void {
    $game = Game::factory()->inSetup()->create();
    $user = User::factory()->create();

    $transition = $game->transitionToPhase(GamePhase::PreGame, $user, 'Starting pre-game');

    expect($game->current_phase)->toBe(GamePhase::PreGame)
        ->and($game->pre_game_started_at)->not->toBeNull()
        ->and($transition->from_phase)->toBe(GamePhase::Setup->value)
        ->and($transition->to_phase)->toBe(GamePhase::PreGame->value);
});

it('can transition to active from pre-game', function (): void {
    $game = Game::factory()->inPreGame()->create();

    $game->transitionToPhase(GamePhase::Active);

    expect($game->current_phase)->toBe(GamePhase::Active)
        ->and($game->game_started_at)->not->toBeNull();
});

it('cannot transition to invalid phase', function (): void {
    $game = Game::factory()->inSetup()->create();

    expect(fn () => $game->transitionToPhase(GamePhase::Active))
        ->toThrow(InvalidArgumentException::class);
});

it('can check if game is playable', function (): void {
    $setupGame = Game::factory()->inSetup()->create();
    $activeGame = Game::factory()->active()->create();

    expect($setupGame->isPlayable())->toBeFalse()
        ->and($activeGame->isPlayable())->toBeTrue();
});

it('can check if game is in setup', function (): void {
    $game = Game::factory()->inSetup()->create();

    expect($game->isInSetup())->toBeTrue();
});

it('can check if game is active', function (): void {
    $game = Game::factory()->active()->create();

    expect($game->isActive())->toBeTrue();
});

it('can check if game has ended', function (): void {
    $activeGame = Game::factory()->active()->create();
    $endedGame = Game::factory()->inEndgame()->create();

    expect($activeGame->hasEnded())->toBeFalse()
        ->and($endedGame->hasEnded())->toBeTrue();
});

it('stores state metadata as json', function (): void {
    $metadata = ['key' => 'value', 'nested' => ['data' => 123]];
    $game = Game::factory()->create(['state_metadata' => $metadata]);

    expect($game->state_metadata)->toBe($metadata);
});

it('stores config as json', function (): void {
    $config = ['max_players' => 50, 'duration_minutes' => 120];
    $game = Game::factory()->create(['config' => $config]);

    expect($game->config)->toBe($config);
});

it('stores rules as json', function (): void {
    $rules = ['allow_jokers' => true, 'safe_zone_time' => 10];
    $game = Game::factory()->create(['rules' => $rules]);

    expect($game->rules)->toBe($rules);
});

it('updates phase timestamps correctly on transition', function (): void {
    $game = Game::factory()->inSetup()->create();

    $game->transitionToPhase(GamePhase::PreGame);
    expect($game->pre_game_started_at)->not->toBeNull();

    $game->transitionToPhase(GamePhase::Active);
    expect($game->game_started_at)->not->toBeNull();
});

it('can create transition with metadata', function (): void {
    $game = Game::factory()->inSetup()->create();
    $user = User::factory()->create();
    $metadata = ['trigger' => 'manual', 'notes' => 'test'];

    $transition = $game->transitionToPhase(
        GamePhase::PreGame,
        $user,
        'Test transition',
        $metadata
    );

    expect($transition->metadata)->toBe($metadata)
        ->and($transition->reason)->toBe('Test transition')
        ->and($transition->user_id)->toBe($user->id);
});

it('validates transition rules', function (): void {
    $game = Game::factory()->inPreGame()->create();

    // Valid transition
    expect($game->current_phase->canTransitionTo(GamePhase::Active))->toBeTrue();

    // Invalid transition
    expect($game->current_phase->canTransitionTo(GamePhase::Endgame))->toBeFalse();
});
