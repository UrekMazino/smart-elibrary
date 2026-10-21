<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class DropAllTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS = 0;');

        // Increase GROUP_CONCAT limit
        DB::statement('SET GROUP_CONCAT_MAX_LEN = 32768;');

        // Build ONE valid `DROP TABLE IF EXISTS `a`,`b`,...` statement. (The previous version
        // chained many `DROP TABLE IF EXISTS `x`` clauses with commas — invalid SQL.) Exclude
        // Laravel's own migrations / personal_access_tokens tables so migrate:fresh doesn't drop
        // the migration-tracking table mid-run.
        $tableList = DB::selectOne(
            "SELECT GROUP_CONCAT(CONCAT('`', table_name, '`')) AS list " .
            "FROM information_schema.tables " .
            "WHERE table_schema = ? AND table_type = 'BASE TABLE' " .
            "AND table_name NOT IN ('migrations','personal_access_tokens')",
            [DB::getDatabaseName()]
        )->list;

        if ($tableList) {
            DB::statement('DROP TABLE IF EXISTS ' . $tableList);
        }

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }

    /**
     * Reverse the migrations (not applicable for dropping tables).
     *
     * @return void
     */
    public function down()
    {
        // Note: Dropping tables is irreversible, so no action is taken here.
        // You could add a comment or log a warning if needed.
    }
}