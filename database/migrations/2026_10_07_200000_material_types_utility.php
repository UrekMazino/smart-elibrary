<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Administration → Utilities → Material Types (CRUD).
 *
 * materials gets:
 *  - is_active    : shown in the Material Type pickers / filters (hidden types stay on their records)
 *  - is_marc_type : the 14 MARC "type of record" entries (LDR/06 codes a, c, d, ... — "Language
 *                   material", "Mixed materials", ...). They are the importer's LDR/06 fallback,
 *                   not library categories: protected (no delete/merge-away/code edit) and hidden
 *                   from the pickers by default, so catalogers pick a real Material Type.
 *  - unique name, created_by / updated_by.
 *
 * Plus the "Manage Material Types" permission (Configuration module), granted to super_admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('tooltip');
            $table->boolean('is_marc_type')->default(false)->after('is_active');
            $table->unsignedBigInteger('created_by')->nullable()->after('is_marc_type');
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            $table->unique('name');
        });

        // MARC record types are the entries keyed by a single LDR/06 letter (library types use '#').
        DB::table('materials')->whereRaw("code REGEXP '^[a-z]$'")->update(['is_marc_type' => true, 'is_active' => false]);

        $permission = Permission::firstOrCreate(
            ['name' => 'manage.material.types', 'guard_name' => 'web'],
            ['display_name' => 'Manage Material Types',
             'description'  => 'Add, edit, hide, merge and delete Material Types (Administration → Utilities)']
        );
        foreach (['super_admin', 'developer'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if ($role && ! $role->hasPermissionTo($permission)) $role->givePermissionTo($permission);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where('name', 'manage.material.types')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Schema::table('materials', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->dropColumn(['is_active', 'is_marc_type', 'created_by', 'updated_by']);
        });
    }
};
