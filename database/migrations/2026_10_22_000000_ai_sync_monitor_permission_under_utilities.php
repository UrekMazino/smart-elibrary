<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The AI Sync Monitor moved into Administration → Utilities; its permission's description (shown on
 * the Roles and Permissions pages) still named the old menu place. Only the untouched text is
 * changed, so a description a super admin rewrote is kept. database/seeders/data/permissions.json
 * carries the new text for fresh installs.
 */
return new class extends Migration
{
    private const OLD = 'Open Administration → AI Sync Monitor: see AI-index sync status, run sync now and re-sync records';
    private const NEW = 'Open Administration → Utilities → AI Sync Monitor: see AI-index sync status, run sync now and re-sync records';

    public function up(): void
    {
        DB::table('permissions')->where('name', 'access.ai.sync.monitor')->where('description', self::OLD)->update(['description' => self::NEW]);
    }

    public function down(): void
    {
        DB::table('permissions')->where('name', 'access.ai.sync.monitor')->where('description', self::NEW)->update(['description' => self::OLD]);
    }
};
