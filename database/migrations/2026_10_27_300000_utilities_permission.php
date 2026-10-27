<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Administration → Utilities gets its own module permission, "Utilities Module Access" (access.utilities). The hub and
 * every utility needed "Configuration/Setup Access", which nothing on the Roles page tied to Utilities; each utility
 * still needs its own permission too (MARC Templates, Material Types, AI Sync Monitor, Theme). Settings keeps
 * access.configuration. It goes to the roles that could open Utilities before (super admin, developer and every role
 * with access.configuration); tick it for another role in Roles. database/seeders/data/{permissions,
 * role_has_permissions}.json carry the same rows (the seeder runs after migrations and truncates those tables).
 */
return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::firstOrCreate(
            ['name' => 'access.utilities', 'guard_name' => 'web'],
            ['display_name' => 'Utilities Module Access', 'description' => 'Open Administration → Utilities; each utility also needs its own permission']
        );
        foreach (Role::where('guard_name', 'web')->get() as $role) {
            $allowed = in_array($role->name, ['super_admin', 'developer'], true) || $role->hasPermissionTo('access.configuration');
            if ($allowed && ! $role->hasPermissionTo($permission)) $role->givePermissionTo($permission);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where('name', 'access.utilities')->where('guard_name', 'web')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
