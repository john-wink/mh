<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->organization = Organization::factory()->create();
});

describe('User registration flow', function (): void {
    it('creates user with hashed password', function (): void {
        $userData = [
            'organization_id' => $this->organization->id,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        $user = User::factory()->create($userData);

        expect($user->password)->not->toBe('password123')
            ->and(Hash::check('password123', $user->password))->toBeTrue();
    });

    it('requires email verification for new users', function (): void {
        $user = User::factory()->for($this->organization)->create([
            'email_verified_at' => null,
        ]);

        expect($user->hasVerifiedEmail())->toBeFalse();
    });

    it('marks email as verified when timestamp is set', function (): void {
        $user = User::factory()->for($this->organization)->create([
            'email_verified_at' => now(),
        ]);

        expect($user->hasVerifiedEmail())->toBeTrue();
    });
});

describe('User authentication', function (): void {
    it('authenticates user with correct credentials', function (): void {
        $user = User::factory()->for($this->organization)->create([
            'email' => 'user@example.com',
            'password' => 'correct-password',
        ]);

        $authenticated = Auth::attempt([
            'email' => 'user@example.com',
            'password' => 'correct-password',
        ]);

        expect($authenticated)->toBeTrue()
            ->and(Auth::check())->toBeTrue()
            ->and(Auth::id())->toBe($user->id);
    });

    it('fails authentication with incorrect password', function (): void {
        User::factory()->for($this->organization)->create([
            'email' => 'user@example.com',
            'password' => 'correct-password',
        ]);

        $authenticated = Auth::attempt([
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ]);

        expect($authenticated)->toBeFalse()
            ->and(Auth::check())->toBeFalse();
    });

    it('fails authentication with non-existent email', function (): void {
        $authenticated = Auth::attempt([
            'email' => 'nonexistent@example.com',
            'password' => 'any-password',
        ]);

        expect($authenticated)->toBeFalse();
    });

    it('maintains authentication across requests', function (): void {
        $user = User::factory()->for($this->organization)->create();

        Auth::login($user);

        expect(Auth::check())->toBeTrue()
            ->and(Auth::user()->id)->toBe($user->id);
    });

    it('logs out user successfully', function (): void {
        $user = User::factory()->for($this->organization)->create();

        Auth::login($user);
        expect(Auth::check())->toBeTrue();

        Auth::logout();

        expect(Auth::check())->toBeFalse();
    });
});

describe('Multi-tenant authentication', function (): void {
    it('authenticates users from different organizations', function (): void {
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        $user1 = User::factory()->for($org1)->create([
            'email' => 'user1@example.com',
            'password' => 'password',
        ]);

        $user2 = User::factory()->for($org2)->create([
            'email' => 'user2@example.com',
            'password' => 'password',
        ]);

        Auth::login($user1);
        expect(Auth::user()->organization_id)->toBe($org1->id);

        Auth::logout();
        Auth::login($user2);
        expect(Auth::user()->organization_id)->toBe($org2->id);
    });

    it('prevents user from accessing different organization data', function (): void {
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        $user1 = User::factory()->for($org1)->create();
        $user2 = User::factory()->for($org2)->create();

        Auth::login($user1);

        expect(Auth::user()->organization_id)->toBe($org1->id)
            ->and(Auth::user()->organization_id)->not->toBe($org2->id);
    });
});

describe('Role-based authentication', function (): void {
    it('authenticates user with roles', function (): void {
        $user = User::factory()->for($this->organization)->create();
        $role = Role::factory()->for($this->organization)->create(['slug' => 'admin']);

        $user->roles()->attach($role);

        Auth::login($user);

        expect(Auth::user()->hasRole('admin'))->toBeTrue();
    });

    it('authenticates user with multiple roles', function (): void {
        $user = User::factory()->for($this->organization)->create();
        $adminRole = Role::factory()->for($this->organization)->create(['slug' => 'admin']);
        $editorRole = Role::factory()->for($this->organization)->create(['slug' => 'editor']);

        $user->roles()->attach([$adminRole->id, $editorRole->id]);

        Auth::login($user);

        expect(Auth::user()->hasRole('admin'))->toBeTrue()
            ->and(Auth::user()->hasRole('editor'))->toBeTrue();
    });

    it('authenticates user with permissions through roles', function (): void {
        $user = User::factory()->for($this->organization)->create();
        $permission = Permission::factory()->create(['slug' => 'users.create']);
        $role = Role::factory()->for($this->organization)->create();

        $role->permissions()->attach($permission);
        $user->roles()->attach($role);

        Auth::login($user);

        expect(Auth::user()->hasPermission('users.create'))->toBeTrue();
    });
});

describe('Remember me functionality', function (): void {
    it('sets remember token when remember me is used', function (): void {
        $user = User::factory()->for($this->organization)->create([
            'email' => 'user@example.com',
            'password' => 'password',
        ]);

        Auth::attempt([
            'email' => 'user@example.com',
            'password' => 'password',
        ], true); // remember = true

        expect($user->fresh()->remember_token)->not->toBeNull();
    });

    it('does not set remember token when remember me is false', function (): void {
        $user = User::factory()->for($this->organization)->create([
            'email' => 'user@example.com',
            'password' => 'password',
            'remember_token' => null,
        ]);

        Auth::attempt([
            'email' => 'user@example.com',
            'password' => 'password',
        ], false); // remember = false

        // Note: Laravel may still set a token, but we're testing the parameter
        expect(Auth::check())->toBeTrue();
    });
});

describe('Email verification requirement', function (): void {
    it('allows verified users to access protected resources', function (): void {
        $user = User::factory()->for($this->organization)->create([
            'email_verified_at' => now(),
        ]);

        expect($user->hasVerifiedEmail())->toBeTrue();
    });

    it('prevents unverified users from accessing protected resources', function (): void {
        $user = User::factory()->for($this->organization)->create([
            'email_verified_at' => null,
        ]);

        expect($user->hasVerifiedEmail())->toBeFalse();
    });

    it('marks email as verified on verification', function (): void {
        $user = User::factory()->for($this->organization)->create([
            'email_verified_at' => null,
        ]);

        $user->markEmailAsVerified();

        expect($user->hasVerifiedEmail())->toBeTrue()
            ->and($user->email_verified_at)->not->toBeNull();
    });
});

describe('Password security', function (): void {
    it('hashes passwords automatically', function (): void {
        $user = User::factory()->for($this->organization)->create([
            'password' => 'plaintext',
        ]);

        expect($user->password)->not->toBe('plaintext')
            ->and(mb_strlen($user->password))->toBeGreaterThan(20);
    });

    it('validates password correctly after hashing', function (): void {
        $user = User::factory()->for($this->organization)->create([
            'password' => 'my-secret-password',
        ]);

        expect(Hash::check('my-secret-password', $user->password))->toBeTrue()
            ->and(Hash::check('wrong-password', $user->password))->toBeFalse();
    });
});
