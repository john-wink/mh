<?php

declare(strict_types=1);

use App\Enums\EventType;
use App\Models\Game;
use App\Models\GameEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create a game event', function (): void {
    $game = Game::factory()->create();
    $user = User::factory()->create();

    $event = GameEvent::factory()->create([
        'game_id' => $game->id,
        'created_by_user_id' => $user->id,
        'title' => 'Test Event',
        'type' => EventType::Manual,
    ]);

    expect($event->game_id)->toBe($game->id)
        ->and($event->created_by_user_id)->toBe($user->id)
        ->and($event->title)->toBe('Test Event')
        ->and($event->type)->toBe(EventType::Manual);
});

it('can soft delete an event', function (): void {
    $event = GameEvent::factory()->create();

    $event->delete();

    expect($event->trashed())->toBeTrue()
        ->and(GameEvent::query()->withTrashed()->find($event->id))->not->toBeNull();
});

it('belongs to a game', function (): void {
    $game = Game::factory()->create();
    $event = GameEvent::factory()->create(['game_id' => $game->id]);

    expect($event->game->id)->toBe($game->id);
});

it('belongs to a user', function (): void {
    $user = User::factory()->create();
    $event = GameEvent::factory()->create(['created_by_user_id' => $user->id]);

    expect($event->createdByUser->id)->toBe($user->id);
});

it('can have null user for automatic events', function (): void {
    $event = GameEvent::factory()->automatic()->create();

    expect($event->created_by_user_id)->toBeNull()
        ->and($event->createdByUser)->toBeNull();
});

it('stores event data as json', function (): void {
    $data = ['location' => ['lat' => 40.7128, 'lng' => -74.0060], 'notes' => 'Test'];
    $event = GameEvent::factory()->create(['data' => $data]);

    expect($event->data)->toBe($data);
});

it('defaults to unprocessed', function (): void {
    $event = GameEvent::factory()->create();

    expect($event->is_processed)->toBeFalse()
        ->and($event->processed_at)->toBeNull();
});

it('can mark event as processed', function (): void {
    $event = GameEvent::factory()->create();

    $result = $event->markAsProcessed();

    expect($result)->toBeTrue()
        ->and($event->fresh()->is_processed)->toBeTrue()
        ->and($event->fresh()->processed_at)->not->toBeNull();
});

it('can mark event as unprocessed', function (): void {
    $event = GameEvent::factory()->processed()->create();

    $result = $event->markAsUnprocessed();

    expect($result)->toBeTrue()
        ->and($event->fresh()->is_processed)->toBeFalse()
        ->and($event->fresh()->processed_at)->toBeNull();
});

it('can filter events by game using scope', function (): void {
    $game1 = Game::factory()->create();
    $game2 = Game::factory()->create();

    GameEvent::factory()->count(3)->create(['game_id' => $game1->id]);
    GameEvent::factory()->count(2)->create(['game_id' => $game2->id]);

    $game1Events = GameEvent::query()->forGame($game1->id)->get();

    expect($game1Events)->toHaveCount(3);
});

it('can filter unprocessed events using scope', function (): void {
    GameEvent::factory()->count(3)->create(['is_processed' => false]);
    GameEvent::factory()->count(2)->processed()->create();

    $unprocessed = GameEvent::query()->unprocessed()->get();

    expect($unprocessed)->toHaveCount(3);
});

it('can filter processed events using scope', function (): void {
    GameEvent::factory()->count(3)->create(['is_processed' => false]);
    GameEvent::factory()->count(2)->processed()->create();

    $processed = GameEvent::query()->processed()->get();

    expect($processed)->toHaveCount(2);
});

it('can filter by event type using scope', function (): void {
    GameEvent::factory()->count(3)->manual()->create();
    GameEvent::factory()->count(2)->automatic()->create();

    $manualEvents = GameEvent::query()->ofType(EventType::Manual)->get();

    expect($manualEvents)->toHaveCount(3);
});

it('can order by priority using scope', function (): void {
    GameEvent::factory()->create(['priority' => 5]);
    GameEvent::factory()->create(['priority' => 10]);
    GameEvent::factory()->create(['priority' => 1]);

    $events = GameEvent::query()->byPriority()->get();

    expect($events->first()->priority)->toBe(10)
        ->and($events->last()->priority)->toBe(1);
});

it('can order chronologically using scope', function (): void {
    $oldest = GameEvent::factory()->create(['occurred_at' => now()->subDays(3)]);
    $newest = GameEvent::factory()->create(['occurred_at' => now()]);
    $middle = GameEvent::factory()->create(['occurred_at' => now()->subDay()]);

    $events = GameEvent::query()->chronological()->get();

    expect($events->first()->id)->toBe($oldest->id)
        ->and($events->last()->id)->toBe($newest->id);
});

it('supports all event types', function (): void {
    $manual = GameEvent::factory()->manual()->create();
    $automatic = GameEvent::factory()->automatic()->create();
    $system = GameEvent::factory()->system()->create();
    $proximityAlert = GameEvent::factory()->proximityAlert()->create();
    $zoneEnter = GameEvent::factory()->zoneEnter()->create();
    $zoneExit = GameEvent::factory()->zoneExit()->create();

    expect($manual->type)->toBe(EventType::Manual)
        ->and($automatic->type)->toBe(EventType::Automatic)
        ->and($system->type)->toBe(EventType::System)
        ->and($proximityAlert->type)->toBe(EventType::ProximityAlert)
        ->and($zoneEnter->type)->toBe(EventType::ZoneEnter)
        ->and($zoneExit->type)->toBe(EventType::ZoneExit);
});

it('can create high priority events', function (): void {
    $event = GameEvent::factory()->highPriority()->create();

    expect($event->priority)->toBeGreaterThanOrEqual(80);
});

it('can create low priority events', function (): void {
    $event = GameEvent::factory()->lowPriority()->create();

    expect($event->priority)->toBeLessThanOrEqual(20);
});
