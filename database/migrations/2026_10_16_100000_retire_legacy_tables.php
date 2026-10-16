<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

/**
 * Retire the pre-MARC model (R13). The previous system kept a title as one 89-column row in
 * `holdings`, with `authors` / `corporate_authors` beside it and loans in `barrowed_books`; the
 * other tables here were never used. Nothing writes them any more: the last code that read them
 * (the Acquisition screens, the old borrow actions) was removed together with this migration.
 *
 * The definitions are archived first to storage/app/archive/legacy_schema_<database>.sql, and
 * down() recreates the objects from that file. A table that still holds rows is never dropped:
 * the migration stops and names it, so its data can be exported first.
 */
return new class extends Migration
{
    /** Views over the legacy tables; dropped before them. */
    private const VIEWS = ['view_borrowed_books', 'view_holding_frontpages', 'view_holdings', 'view_holdings_uncatalog'];

    private const TABLES = [
        'holdings', 'authors', 'corporate_authors', 'barrowed_books', 'thumbnails', 'fullpages',
        'p_d_f_s', 'for_reviews', 'view_total_copies', 'visits', 'marc_templates_backup',
    ];

    public function up(): void
    {
        $tables = array_values(array_filter(self::TABLES, fn ($t) => Schema::hasTable($t)));
        $views = array_values(array_filter(self::VIEWS, fn ($v) => $this->isView($v)));

        $full = array_filter($tables, fn ($t) => DB::table($t)->exists());
        if ($full) {
            throw new RuntimeException('Not changed: these legacy tables still hold rows: '
                . implode(', ', array_map(fn ($t) => "$t (" . DB::table($t)->count() . ')', $full))
                . '. Export their data, empty them, then migrate again.');
        }

        // Tables first in the archive, so the views can be recreated on top of them.
        $sql = ['-- Legacy objects dropped by ' . basename(__FILE__) . ' on ' . now()->toDateTimeString(), 'SET FOREIGN_KEY_CHECKS=0;'];
        foreach ($tables as $t) {
            $sql[] = DB::selectOne("SHOW CREATE TABLE `$t`")->{'Create Table'} . ';';
        }
        foreach ($views as $v) {
            // Without the DEFINER, the view is recreated as whoever runs the rollback.
            $sql[] = preg_replace('/ DEFINER=`[^`]*`@`[^`]*`/', '', DB::selectOne("SHOW CREATE VIEW `$v`")->{'Create View'}) . ';';
        }
        $sql[] = 'SET FOREIGN_KEY_CHECKS=1;';
        File::ensureDirectoryExists(dirname($this->archive()));
        File::put($this->archive(), implode("\n\n", $sql) . "\n");

        foreach ($views as $v) DB::statement("DROP VIEW `$v`");
        foreach ($tables as $t) Schema::drop($t);
    }

    public function down(): void
    {
        if (! is_file($this->archive())) {
            throw new RuntimeException('Cannot recreate the legacy tables: ' . $this->archive() . ' is missing.');
        }
        DB::unprepared(File::get($this->archive()));
    }

    /** One file per database, so a test run never overwrites the live database's archive. */
    private function archive(): string
    {
        return storage_path('app/archive/legacy_schema_' . DB::getDatabaseName() . '.sql');
    }

    private function isView(string $name): bool
    {
        return DB::table('information_schema.views')->where('table_schema', DB::getDatabaseName())->where('table_name', $name)->exists();
    }
};
