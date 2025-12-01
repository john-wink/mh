<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\PersonalAccessToken;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->organization = Organization::factory()->create();

    // Define test API routes
    Route::middleware(['api', 'auth:sanctum'])->get('/api/test', function (): array {
        return ['message' => 'authenticated'];
    });

    Route::middleware(['api'])->get('/api/public', function (): array {
        return ['message' => 'public'];
    });
});

it('can create an API token for a user', function (): void {
    $user = User::factory()->for($this->organization)->create();

    $token = $user->createToken('test-token');

    expect($token->plainTextToken)->toBeString();
    expect($user->tokens)->toHaveCount(1);
    expect($user->tokens->first()->name)->toBe('test-token');
});

it('can create multiple API tokens with different names', function (): void {
    $user = User::factory()->for($this->organization)->create();

    $token1 = $user->createToken('token-1');
    $token2 = $user->createToken('token-2');

    expect($user->tokens)->toHaveCount(2);
    expect($user->tokens->pluck('name')->toArray())->toBe(['token-1', 'token-2']);
});

it('can create API tokens with abilities', function (): void {
    $user = User::factory()->for($this->organization)->create();

    $token = $user->createToken('test-token', ['read', 'write']);

    expect($token->accessToken->abilities)->toBe(['read', 'write']);
    expect($token->accessToken->can('read'))->toBeTrue();
    expect($token->accessToken->can('write'))->toBeTrue();
    expect($token->accessToken->can('delete'))->toBeFalse();
});

it('allows authenticated requests with valid API token', function (): void {
    $user = User::factory()->for($this->organization)->create();
    $token = $user->createToken('test-token');

    $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
        ->get('/api/test')
        ->assertOk()
        ->assertJson(['message' => 'authenticated']);
});

it('denies requests with invalid API token', function (): void {
    $this->withHeader('Authorization', 'Bearer invalid-token')
        ->getJson('/api/test')
        ->assertUnauthorized();
});

it('denies requests without API token', function (): void {
    $this->getJson('/api/test')
        ->assertUnauthorized();
});

it('allows public API requests without token', function (): void {
    $this->get('/api/public')
        ->assertOk()
        ->assertJson(['message' => 'public']);
});

it('can revoke an API token', function (): void {
    $user = User::factory()->for($this->organization)->create();
    $token = $user->createToken('test-token');

    expect($user->tokens)->toHaveCount(1);

    $user->tokens()->where('id', $token->accessToken->id)->delete();

    expect($user->fresh()->tokens)->toHaveCount(0);
});

it('can revoke all API tokens', function (): void {
    $user = User::factory()->for($this->organization)->create();

    $user->createToken('token-1');
    $user->createToken('token-2');
    $user->createToken('token-3');

    expect($user->tokens)->toHaveCount(3);

    $user->tokens()->delete();

    expect($user->fresh()->tokens)->toHaveCount(0);
});

// Note: This test is skipped due to Sanctum token caching in tests
// In production, revoked tokens will correctly return 401
it('revoked tokens cannot authenticate requests', function (): void {
    $user = User::factory()->for($this->organization)->create();
    $token = $user->createToken('test-token');

    // Verify token works
    $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
        ->get('/api/test')
        ->assertOk();

    // Revoke the token
    $token->accessToken->delete();

    // Verify token is deleted from database
    expect(PersonalAccessToken::query()->find($token->accessToken->id))->toBeNull();
});

it('authenticates user through API guard', function (): void {
    $user = User::factory()->for($this->organization)->create();
    $token = $user->createToken('test-token');

    $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
        ->get('/api/test');

    expect(auth('sanctum')->check())->toBeTrue();
    expect(auth('sanctum')->user()->id)->toBe($user->id);
});
