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

        // Generate DROP TABLE statements for all base tables
        $dropList = DB::selectOne(
            "SELECT GROUP_CONCAT(CONCAT('DROP TABLE IF EXISTS `', table_name, '`')) AS statement " .
            "FROM information_schema.tables " .
            "WHERE table_schema = ? AND table_type = 'BASE TABLE'",
            [DB::getDatabaseName()]
        )->statement;

        if ($dropList) {
            // Execute the DROP TABLE statements
            DB::statement($dropList);
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