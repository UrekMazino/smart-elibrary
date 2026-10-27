<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Administration → Accounts → Signed in now (SignedInNowController): who is signed in, and signing one person or
 * everyone out (OWASP ASVS 7.4.5). The permission goes to super admin and developer; tick it for another role in
 * Roles. database/seeders/data/{permissions,role_has_permissions}.json carry the same rows (the seeder runs after
 * migrations and truncates those tables).
 */
return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::firstOrCreate(
            ['name' => 'access.sessions', 'guard_name' => 'web'],
            ['display_name' => 'Signed in now', 'description' => 'Open Administration → Accounts → Signed in now: see who is signed in, and sign one person or everyone out']
        );
        foreach (Role::where('guard_name', 'web')->whereIn('name', ['super_admin', 'developer'])->get() as $role) {
            if (! $role->hasPermissionTo($permission)) $role->givePermissionTo($permission);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where('name', 'access.sessions')->where('guard_name', 'web')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
