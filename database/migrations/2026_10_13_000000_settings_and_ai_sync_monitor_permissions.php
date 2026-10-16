<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Administration → Settings and Administration → AI Sync Monitor get their own permissions, so a
 * role can be given one without the other (Roles matrix, Configuration module).
 *
 * Until now Settings opened for anyone with access.configuration, and the AI Sync Monitor (incl.
 * Run Sync Now) for anyone with access.cataloging, shown under Administration. To change nothing on
 * upgrade, the new permissions go to the roles that could use those pages before:
 *  - access.settings        → every role with access.configuration
 *  - access.ai.sync.monitor → every role with access.configuration AND access.cataloging
 * Super admin and developer always get both. Untick them in Roles to take a page away.
 *
 * database/seeders/data/{permissions,role_has_permissions}.json carry the same rows (the seeder
 * runs after migrations and truncates those tables).
 */
return new class extends Migration
{
    private const PERMISSIONS = [
        'access.settings' => ['Settings', 'Open Administration → Settings: feature switches, cache, search index and sitemap tools'],
        'access.ai.sync.monitor' => ['AI Sync Monitor', 'Open Administration → AI Sync Monitor: see AI-index sync status, run sync now and re-sync records'],
    ];

    public function up(): void
    {
        $made = [];
        foreach (self::PERMISSIONS as $name => [$label, $description]) {
            $made[$name] = Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['display_name' => $label, 'description' => $description]
            );
        }

        foreach (Role::where('guard_name', 'web')->get() as $role) {
            $top    = in_array($role->name, ['super_admin', 'developer'], true);
            $config = $role->hasPermissionTo('access.configuration');
            $catalog = $role->hasPermissionTo('access.cataloging');

            if (($top || $config) && ! $role->hasPermissionTo($made['access.settings'])) {
                $role->givePermissionTo($made['access.settings']);
            }
            if (($top || ($config && $catalog)) && ! $role->hasPermissionTo($made['access.ai.sync.monitor'])) {
                $role->givePermissionTo($made['access.ai.sync.monitor']);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::whereIn('name', array_keys(self::PERMISSIONS))->where('guard_name', 'web')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
