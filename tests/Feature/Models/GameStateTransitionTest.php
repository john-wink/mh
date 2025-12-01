<?php

declare(strict_types=1);

use App\Enums\GamePhase;
use App\Models\Game;
use App\Models\GameStateTransition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create a state transition', function (): void {
    $game = Game::factory()->create();
    $user = User::factory()->create();

    $transition = GameStateTransition::factory()->create([
        'game_id' => $game->id,
        'user_id' => $user->id,
        'from_phase' => GamePhase::Setup->value,
        'to_phase' => GamePhase::PreGame->value,
    ]);

    expect($transition->game_id)->toBe($game->id)
        ->and($transition->user_id)->toBe($user->id)
        ->and($transition->from_phase)->toBe(GamePhase::Setup->value)
        ->and($transition->to_phase)->toBe(GamePhase::PreGame->value);
});

it('belongs to a game', function (): void {
    $game = Game::factory()->create();
    $transition = GameStateTransition::factory()->create(['game_id' => $game->id]);

    expect($transition->game->id)->toBe($game->id);
});

it('belongs to a user', function (): void {
    $user = User::factory()->create();
    $transition = GameStateTransition::factory()->create(['user_id' => $user->id]);

    expect($transition->user->id)->toBe($user->id);
});

it('can have null user for automatic transitions', function (): void {
    $transition = GameStateTransition::factory()->create(['user_id' => null]);

    expect($transition->user_id)->toBeNull()
        ->and($transition->user)->toBeNull();
});

it('stores metadata as json', function (): void {
    $metadata = ['trigger' => 'automatic', 'conditions_met' => true];
    $transition = GameStateTransition::factory()->create(['metadata' => $metadata]);

    expect($transition->metadata)->toBe($metadata);
});

it('defaults to valid transition', function (): void {
    $transition = GameStateTransition::factory()->create();

    expect($transition->is_valid)->toBeTrue();
});

it('can mark transition as valid', function (): void {
    $transition = GameStateTransition::factory()->invalid()->create();

    $result = $transition->markAsValid('Approved by admin');

    expect($result)->toBeTrue()
        ->and($transition->fresh()->is_valid)->toBeTrue()
        ->and($transition->fresh()->validation_notes)->toBe('Approved by admin');
});

it('can mark transition as invalid', function (): void {
    $transition = GameStateTransition::factory()->create();

    $result = $transition->markAsInvalid('Invalid state change');

    expect($result)->toBeTrue()
        ->and($transition->fresh()->is_valid)->toBeFalse()
        ->and($transition->fresh()->validation_notes)->toBe('Invalid state change');
});

it('can get from phase as enum', function (): void {
    $transition = GameStateTransition::factory()->fromSetupToPreGame()->create();

    expect($transition->getFromPhaseEnum())->toBe(GamePhase::Setup);
});

it('can get to phase as enum', function (): void {
    $transition = GameStateTransition::factory()->fromSetupToPreGame()->create();

    expect($transition->getToPhaseEnum())->toBe(GamePhase::PreGame);
});

it('stores transition timestamp', function (): void {
    $now = now();
    $transition = GameStateTransition::factory()->create(['transitioned_at' => $now]);

    expect($transition->transitioned_at->toDateTimeString())
        ->toBe($now->toDateTimeString());
});

it('can store optional reason', function (): void {
    $transition = GameStateTransition::factory()
        ->withReason('Manual game start')
        ->create();

    expect($transition->reason)->toBe('Manual game start');
});

it('can create transitions for all phase combinations', function (): void {
    $setupToPreGame = GameStateTransition::factory()->fromSetupToPreGame()->create();
    $preGameToActive = GameStateTransition::factory()->fromPreGameToActive()->create();
    $activeToEndgame = GameStateTransition::factory()->fromActiveToEndgame()->create();
    $endgameToPostGame = GameStateTransition::factory()->fromEndgameToPostGame()->create();

    expect($setupToPreGame->from_phase)->toBe(GamePhase::Setup->value)
        ->and($setupToPreGame->to_phase)->toBe(GamePhase::PreGame->value)
        ->and($preGameToActive->from_phase)->toBe(GamePhase::PreGame->value)
        ->and($preGameToActive->to_phase)->toBe(GamePhase::Active->value)
        ->and($activeToEndgame->from_phase)->toBe(GamePhase::Active->value)
        ->and($activeToEndgame->to_phase)->toBe(GamePhase::Endgame->value)
        ->and($endgameToPostGame->from_phase)->toBe(GamePhase::Endgame->value)
        ->and($endgameToPostGame->to_phase)->toBe(GamePhase::PostGame->value);
});
