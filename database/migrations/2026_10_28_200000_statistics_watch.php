<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Administration → Accounts → Statistics watch (StatisticsWatchController; docs/not-implemented/RANKING_INTEGRITY_PLAN.md
 * step 2.6): unusual activity, and taking a patron's views, downloads and ratings out of the top-10 lists.
 *
 * ranking_exclusions: one row each time an account is taken out, with who did it, when and why; putting it back
 * fills lifted_by and lifted_at, so the history stays. An account is out while it has a row with no lifted_at
 * (rankingRows()). The permission goes to super admin and developer; tick it for another role in Roles.
 * database/seeders/data/{permissions,role_has_permissions}.json carry the same rows (the seeder runs after
 * migrations and truncates those tables).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ranking_exclusions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('reason', 255)->nullable();
            $table->foreignId('excluded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();
            $table->foreignId('lifted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('lifted_at')->nullable();
            $table->index(['user_id', 'lifted_at']);
        });

        $permission = Permission::firstOrCreate(
            ['name' => 'access.statistics.watch', 'guard_name' => 'web'],
            ['display_name' => 'Statistics watch', 'description' => 'Open Administration → Accounts → Statistics watch: see unusual views, downloads, ratings and sign-ups, and take an account out of the top-10 lists or put it back']
        );
        foreach (Role::where('guard_name', 'web')->whereIn('name', ['super_admin', 'developer'])->get() as $role) {
            if (! $role->hasPermissionTo($permission)) $role->givePermissionTo($permission);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where('name', 'access.statistics.watch')->where('guard_name', 'web')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Schema::dropIfExists('ranking_exclusions');
    }
};
