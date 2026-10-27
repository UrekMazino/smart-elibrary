<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Appearance (App\Services\Appearance): each staff member's own choice of colours, text size, motion
 * and font (users.appearance, null = the library default), and the permission to set that default
 * under Administration → Utilities → Theme.
 *
 * manage.theme goes to the roles that could change Settings (super admin, developer and every role
 * with access.configuration); untick it in Roles to take it away. database/seeders/data/
 * {permissions,role_has_permissions}.json carry the same rows (the seeder runs after migrations and
 * truncates those tables).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('appearance')->nullable();
        });

        $theme = Permission::firstOrCreate(
            ['name' => 'manage.theme', 'guard_name' => 'web'],
            ['display_name' => 'Theme', 'description' => 'Open Administration → Utilities → Theme: set the appearance every staff member starts with (each can still choose their own)']
        );
        foreach (Role::where('guard_name', 'web')->get() as $role) {
            $allowed = in_array($role->name, ['super_admin', 'developer'], true) || $role->hasPermissionTo('access.configuration');
            if ($allowed && ! $role->hasPermissionTo($theme)) $role->givePermissionTo($theme);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where('name', 'manage.theme')->where('guard_name', 'web')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('appearance');
        });
    }
};
