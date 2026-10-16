<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * One character set, one collation and one engine for every table (R12).
 *
 * The baseline dump left four combinations: InnoDB utf8mb4_unicode_ci (most tables, and what
 * config/database.php declares for new ones), InnoDB utf8mb4_general_ci (the cataloguing and
 * circulation tables), InnoDB latin1 (frontpages, materials, multimedia: their data is plain
 * ASCII, so nothing is lost) and MyISAM utf8mb3 (regions, which could not take part in a
 * transaction). Comparing string columns across collations fails with "Illegal mix of
 * collations" or forces a conversion that skips the index; latin1 cannot hold a title in any
 * script outside Western Europe.
 *
 * Every base table whose engine, table collation or any column collation differs is rebuilt as
 * InnoDB utf8mb4_unicode_ci, so a re-run after a partial failure picks up where it stopped. The
 * rebuild rewrites each table: run it in a maintenance window on a large database.
 */
return new class extends Migration
{
    private const COLLATION = 'utf8mb4_unicode_ci';

    /** How the tables were before: table => [engine, collation]. down() puts them back. */
    private const ORIGINAL = [
        'catalog_import_progress'  => ['InnoDB', 'utf8mb4_general_ci'],
        'catalog_imports'          => ['InnoDB', 'utf8mb4_general_ci'],
        'catalogs'                 => ['InnoDB', 'utf8mb4_general_ci'],
        'circulation_fine_history' => ['InnoDB', 'utf8mb4_general_ci'],
        'circulation_fines'        => ['InnoDB', 'utf8mb4_general_ci'],
        'circulation_reservations' => ['InnoDB', 'utf8mb4_general_ci'],
        'circulation_status'       => ['InnoDB', 'utf8mb4_general_ci'],
        'circulations'             => ['InnoDB', 'utf8mb4_general_ci'],
        'copies'                   => ['InnoDB', 'utf8mb4_general_ci'],
        'copies_status'            => ['InnoDB', 'utf8mb4_general_ci'],
        'marc_class_sys'           => ['InnoDB', 'utf8mb4_general_ci'],
        'marc_templates'           => ['InnoDB', 'utf8mb4_general_ci'],
        'marctag_indicators_loc'   => ['InnoDB', 'utf8mb4_general_ci'],
        'view_catalogs'            => ['InnoDB', 'utf8mb4_general_ci'],
        'frontpages'               => ['InnoDB', 'latin1_swedish_ci'],
        'materials'                => ['InnoDB', 'latin1_swedish_ci'],
        'multimedia'               => ['InnoDB', 'latin1_swedish_ci'],
        'regions'                  => ['MyISAM', 'utf8mb3_general_ci'],
    ];

    /**
     * Columns down() must set explicitly: [table, column, definition]. Two had a collation of their
     * own; two were TEXT, which the conversion to utf8mb4 widens to MEDIUMTEXT to keep their
     * capacity in characters, and converting back does not narrow again.
     */
    private const ORIGINAL_COLUMNS = [
        ['catalogs', 'field_data', 'LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL'],
        ['holdingscopypresenteds', 'circulation_number', 'VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL'],
        ['materials', 'tooltip', 'TEXT CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL'],
        ['regions', 'desc', 'TEXT CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL'],
    ];

    public function up(): void
    {
        $db = DB::getDatabaseName();
        $tables = DB::table('information_schema.tables as t')
            ->where('t.table_schema', $db)->where('t.table_type', 'BASE TABLE')
            ->where(fn ($q) => $q->where('t.engine', '<>', 'InnoDB')->orWhere('t.table_collation', '<>', self::COLLATION)
                ->orWhereExists(fn ($c) => $c->select(DB::raw(1))->from('information_schema.columns as c')
                    ->whereColumn('c.table_schema', 't.table_schema')->whereColumn('c.table_name', 't.table_name')
                    ->whereNotNull('c.collation_name')->where('c.collation_name', '<>', self::COLLATION)))
            ->orderBy('t.table_name')
            ->get(['t.table_name as name', 't.engine as engine'])
            ->map(fn ($t) => [$t->name, $t->engine]);

        DB::statement("ALTER DATABASE `$db` CHARACTER SET utf8mb4 COLLATE " . self::COLLATION);
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            foreach ($tables as [$table, $engine]) {
                if ($engine !== 'InnoDB') DB::statement("ALTER TABLE `$table` ENGINE=InnoDB");
                DB::statement("ALTER TABLE `$table` CONVERT TO CHARACTER SET utf8mb4 COLLATE " . self::COLLATION);
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            foreach (self::ORIGINAL as $table => [$engine, $collation]) {
                $charset = explode('_', $collation)[0];
                DB::statement("ALTER TABLE `$table` CONVERT TO CHARACTER SET $charset COLLATE $collation");
                if ($engine !== 'InnoDB') DB::statement("ALTER TABLE `$table` ENGINE=$engine");
            }
            foreach (self::ORIGINAL_COLUMNS as [$table, $column, $definition]) {
                DB::statement("ALTER TABLE `$table` MODIFY `$column` $definition");
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }
};
