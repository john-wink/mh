<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

it('can request a password reset for a user within their organization', function (): void {
    Notification::fake();

    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'email' => 'test@example.com',
    ]);

    $status = Password::sendResetLink([
        'email' => $user->email,
    ]);

    expect($status)->toBe(Password::RESET_LINK_SENT);

    Notification::assertSentTo($user, ResetPassword::class);

    assertDatabaseHas('password_reset_tokens', [
        'organization_id' => $organization->id,
        'email' => $user->email,
    ]);
});

it('scopes password reset tokens to organization', function (): void {
    $organization1 = Organization::factory()->create();
    $organization2 = Organization::factory()->create();

    $user1 = User::factory()->for($organization1)->create([
        'email' => 'user@example.com',
    ]);
    $user2 = User::factory()->for($organization2)->create([
        'email' => 'user@example.com',
    ]);

    Password::sendResetLink(['email' => $user1->email]);
    Password::sendResetLink(['email' => $user2->email]);

    assertDatabaseCount('password_reset_tokens', 2);

    assertDatabaseHas('password_reset_tokens', [
        'organization_id' => $organization1->id,
        'email' => $user1->email,
    ]);

    assertDatabaseHas('password_reset_tokens', [
        'organization_id' => $organization2->id,
        'email' => $user2->email,
    ]);
});

it('can reset password with valid token', function (): void {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'email' => 'test@example.com',
        'password' => 'old-password',
    ]);

    $token = Password::createToken($user);

    $status = Password::reset([
        'email' => $user->email,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
        'token' => $token,
    ], function ($user, $password): void {
        $user->password = Hash::make($password);
        $user->save();
    });

    expect($status)->toBe(Password::PASSWORD_RESET);

    $user->refresh();

    expect(Hash::check('new-password', $user->password))->toBeTrue();
});

it('cannot reset password with invalid token', function (): void {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'email' => 'test@example.com',
    ]);

    $status = Password::reset([
        'email' => $user->email,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
        'token' => 'invalid-token',
    ], function ($user, $password): void {
        $user->password = Hash::make($password);
        $user->save();
    });

    expect($status)->toBe(Password::INVALID_TOKEN);
});

it('deletes password reset token after successful reset', function (): void {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'email' => 'test@example.com',
    ]);

    $token = Password::createToken($user);

    assertDatabaseHas('password_reset_tokens', [
        'organization_id' => $organization->id,
        'email' => $user->email,
    ]);

    Password::reset([
        'email' => $user->email,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
        'token' => $token,
    ], function ($user, $password): void {
        $user->password = Hash::make($password);
        $user->save();
    });

    assertDatabaseCount('password_reset_tokens', 0);
});
