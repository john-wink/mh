<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->organization = Organization::factory()->create();
    $this->user = User::factory()->create([
        'organization_id' => $this->organization->id,
        'email' => 'test@posteo.de',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
});

it('can access the management login page', function (): void {
    $page = visit('/management/login');

    $page->assertSee(__('filament-panels::auth/pages/login.form.actions.authenticate.label'))
        ->assertNoJavascriptErrors();
});

it('can login to the management panel', function (): void {
    $page = visit('/management/login');

    $page->type('input[type="email"]', 'test@posteo.de')
        ->type('input[type="password"]', 'password')
        ->click('button[type="submit"]')
        ->wait(2)
        ->assertPathIs('/management')
        ->assertSee('Dashboard')
        ->assertNoJavascriptErrors();
});

it('shows validation error with invalid credentials', function (): void {
    $page = visit('/management/login');

    $page->type('input[type="email"]', 'wrong@posteo.de')
        ->type('input[type="password"]', 'wrongpassword')
        ->click('button[type="submit"]')
        ->wait(1)
        ->assertPathIs('/management/login')
        ->assertNoJavascriptErrors();
});

it('requires email verification to access panel', function (): void {
    $unverifiedUser = User::factory()->create([
        'organization_id' => $this->organization->id,
        'email' => 'unverified@posteo.de',
        'password' => bcrypt('password'),
        'email_verified_at' => null,
    ]);

    $page = visit('/management/login');

    $page->type('input[type="email"]', 'unverified@posteo.de')
        ->type('input[type="password"]', 'password')
        ->click('button[type="submit"]')
        ->wait(1)
        ->assertPathIs('/management/login');
});
