<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\TableNameTrait;
use App\Traits\UuidTrait;
use Carbon\CarbonInterface;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property-read int $id
 * @property-read int $organization_id
 * @property-read string $name
 * @property-read string $email
 * @property-read CarbonInterface|null $email_verified_at
 * @property-read string $password
 * @property-read string|null $remember_token
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read CarbonInterface|null $deleted_at
 */
final class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use BelongsToTenant, HasApiTokens, HasFactory, Notifiable, SoftDeletes, TableNameTrait, UuidTrait;

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get validation rules for creating a user
     *
     * @return array<string, mixed>
     */
    public static function createRules(): array
    {
        return [
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    /**
     * Get validation rules for updating a user
     *
     * @return array<string, mixed>
     */
    public static function updateRules(int $userId): array
    {
        return [
            'organization_id' => ['sometimes', 'integer', 'exists:organizations,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', 'unique:users,email,'.$userId],
            'password' => ['sometimes', 'string', 'min:8', 'confirmed'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'integer',
            'organization_id' => 'integer',
            'name' => 'string',
            'email' => 'string',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'remember_token' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole(string $roleSlug): bool
    {
        return $this->roles()->where('slug', $roleSlug)->exists();
    }

    /**
     * Check if user has any of the given roles
     *
     * @param  array<string>  $roleSlugs
     */
    public function hasAnyRole(array $roleSlugs): bool
    {
        return $this->roles()->whereIn('slug', $roleSlugs)->exists();
    }

    /**
     * Check if user has all of the given roles
     *
     * @param  array<string>  $roleSlugs
     */
    public function hasAllRoles(array $roleSlugs): bool
    {
        $userRoles = $this->roles()->pluck('slug')->toArray();

        return count(array_intersect($roleSlugs, $userRoles)) === count($roleSlugs);
    }

    /**
     * Check if user has a specific permission
     */
    public function hasPermission(string $permissionSlug): bool
    {
        return $this->roles()
            ->whereHas('permissions', function (Builder $query) use ($permissionSlug): void {
                $query->where('slug', $permissionSlug);
            })
            ->exists();
    }

    /**
     * Check if user has any of the given permissions
     *
     * @param  array<string>  $permissionSlugs
     */
    public function hasAnyPermission(array $permissionSlugs): bool
    {
        return $this->roles()
            ->whereHas('permissions', function (Builder $query) use ($permissionSlugs): void {
                $query->whereIn('slug', $permissionSlugs);
            })
            ->exists();
    }

    /**
     * Scope to filter users by organization
     *
     * @param  \Illuminate\Database\Eloquent\Builder<self>  $query
     */
    #[Scope]
    protected function forOrganization($query, int $organizationId): void
    {
        $query->where('organization_id', $organizationId);
    }

    /**
     * Scope to filter users by verified email
     *
     * @param  \Illuminate\Database\Eloquent\Builder<self>  $query
     */
    #[Scope]
    protected function verified($query): void
    {
        $query->whereNotNull('email_verified_at');
    }

    /**
     * Scope to filter users by unverified email
     *
     * @param  \Illuminate\Database\Eloquent\Builder<self>  $query
     */
    #[Scope]
    protected function unverified($query): void
    {
        $query->whereNull('email_verified_at');
    }
}
