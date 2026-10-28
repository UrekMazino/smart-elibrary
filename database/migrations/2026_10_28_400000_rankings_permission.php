<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Administration → Utilities → Rankings (RankingsController): the home page's top-10 lists with the numbers behind
 * them, and refreshing them there. A utility: it also needs access.utilities. The permission goes to super admin,
 * system admin and developer; tick it for another role in Roles. database/seeders/data/{permissions,
 * role_has_permissions}.json carry the same rows (the seeder runs after migrations and truncates those tables).
 */
return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::firstOrCreate(
            ['name' => 'access.rankings', 'guard_name' => 'web'],
            ['display_name' => 'Rankings', 'description' => 'Open Administration → Utilities → Rankings: the top-10 lists with the numbers behind them, and refresh them on the home page']
        );
        foreach (Role::where('guard_name', 'web')->whereIn('name', ['super_admin', 'system_admin', 'developer'])->get() as $role) {
            if (! $role->hasPermissionTo($permission)) $role->givePermissionTo($permission);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where('name', 'access.rankings')->where('guard_name', 'web')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
