<?php

declare(strict_types=1);

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $o = Organization::create([
            'name' => 'CyQuer',
            'slug' => 'cyquer',
        ]);

        // Create super admin permission
        $superAdminPermission = Permission::create([
            'slug' => 'super-admin',
            'name' => 'Super Admin',
        ]);

        // Create admin role with permission
        $adminRole = Role::create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'organization_id' => $o->getKey(),
        ]);
        $adminRole->permissions()->attach($superAdminPermission);

        // Create user with admin role
        $user = User::create([
            'organization_id' => $o->getKey(),
            'name' => 'John Wink',
            'email' => 'johnwink@posteo.de',
            'password' => 'password',
            'email_verified_at' => now(),
        ]);
        $user->roles()->attach($adminRole);
    }
}
