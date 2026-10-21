<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Declare the links the application relies on as foreign keys (until now only the permission
 * pivots and saved_records had any), so MySQL rejects a row that points at nothing.
 *
 * What happens when the parent row is deleted (catalogues and accounts are only ever soft-deleted
 * by the app, so this matters for manual deletes and future code):
 *  - cascade   rows that only describe the parent: the OPAC projection, attachments, activity
 *              logs, reviews, read receipts.
 *  - set null  page-view and download counts survive an account's deletion, anonymised; a loan
 *              keeps its barcode text if its copy is deleted.
 *  - restrict  history that must not vanish: copies, circulation, inquiries, survey answers.
 *
 * Not declared: rag_sync_queue.catalog_id (the outbox must outlive a deleted record to remove it
 * from the AI index), model_has_roles.model_id (polymorphic), users.region_id (regions is MyISAM).
 *
 * Rows that already point at a missing parent get their key's rule (cascade: deleted; set null:
 * cleared; logged). Under restrict they stop the migration before anything changes.
 */
return new class extends Migration
{
    /** [table, column, parent table, parent column, on delete] */
    private const KEYS = [
        ['view_catalogs',            'catalog_id',          'catalogs',          'id', 'cascade'],
        ['multimedia',               'catalog_id',          'catalogs',          'id', 'cascade'],
        ['frontpages',               'catalog_id',          'catalogs',          'id', 'cascade'],
        ['views',                    'catalog_id',          'catalogs',          'id', 'cascade'],
        ['downloads',                'catalog_id',          'catalogs',          'id', 'cascade'],
        ['reads',                    'catalog_id',          'catalogs',          'id', 'cascade'],
        ['ratings',                  'catalog_id',          'catalogs',          'id', 'cascade'],
        ['holdingscopypresenteds',   'catalog_id',          'catalogs',          'id', 'cascade'],
        ['copies',                   'catalog_id',          'catalogs',          'id', 'restrict'],
        ['circulations',             'catalog_id',          'catalogs',          'id', 'restrict'],
        ['inquiries',                'catalog_id',          'catalogs',          'id', 'restrict'],
        ['catalogs',                 'catalog_import_id',   'catalog_imports',   'id', 'set null'],
        ['catalogs',                 'material_type_id',    'materials',         'id', 'restrict'],
        ['catalog_imports',          'material_type_id',    'materials',         'id', 'restrict'],
        ['views',                    'user_id',             'users',             'id', 'set null'],
        ['downloads',                'user_id',             'users',             'id', 'set null'],
        ['reads',                    'user_id',             'users',             'id', 'cascade'],
        ['ratings',                  'user_id',             'users',             'id', 'cascade'],
        ['inquiry_seens',            'user_id',             'users',             'id', 'cascade'],
        ['csfs',                     'user_id',             'users',             'id', 'restrict'],
        ['circulations',             'user_id',             'users',             'id', 'restrict'],
        ['circulation_reservations', 'user_id',             'users',             'id', 'restrict'],
        ['inquiries',                'user_id',             'users',             'id', 'restrict'],
        ['reply_inquiries',          'user_id',             'users',             'id', 'restrict'],
        ['reservation_messages',     'user_id',             'users',             'id', 'restrict'],
        ['reservation_messages',     'sender_id',           'users',             'id', 'restrict'],
        ['users',                    'patrontypes_id',      'patrontypes',       'id', 'restrict'],
        ['circulations',             'copy_id',             'copies',            'id', 'set null'],
        ['circulation_fine_history', 'circulation_fine_id', 'circulation_fines', 'id', 'restrict'],
        ['reply_inquiries',          'inquiry_id',          'inquiries',         'id', 'cascade'],
        ['inquiry_seens',            'inquiry_id',          'inquiries',         'id', 'cascade'],
        ['marctag_subfields_loc',    'marctags_loc_id',     'marctags_loc',      'id', 'cascade'],
    ];

    public function up(): void
    {
        // Rows whose parent was already deleted get what the key will do from now on: cascade
        // deletes them, set null clears the reference. Orphans under restrict are history, so
        // they stop the migration before anything changes.
        $orphans = [];
        $blocked = [];
        foreach (self::KEYS as [$table, $column, $parent, $key, $onDelete]) {
            $count = $this->orphans($table, $column, $parent, $key)->count();
            if (! $count) continue;
            $orphans[] = [$table, $column, $parent, $key, $onDelete, $count];
            if ($onDelete === 'restrict') $blocked[] = "$table.$column -> $parent ($count)";
        }
        if ($blocked) {
            throw new RuntimeException('Not changed: rows point at missing parents: ' . implode(', ', $blocked)
                . '. Correct or clear those references, then migrate again.');
        }
        foreach ($orphans as [$table, $column, $parent, $key, $onDelete, $count]) {
            $ids = $this->orphans($table, $column, $parent, $key)->pluck('c.id');
            $onDelete === 'cascade'
                ? DB::table($table)->whereIn('id', $ids)->delete()
                : DB::table($table)->whereIn('id', $ids)->update([$column => null]);
            Log::notice("add_foreign_keys: $table.$column -> $parent: $count orphan row(s) " . ($onDelete === 'cascade' ? 'deleted' : 'set to NULL'));
        }

        // The projection is one row per record.
        if (! $this->hasIndex('view_catalogs', 'view_catalogs_catalog_id_unique')) {
            Schema::table('view_catalogs', fn (Blueprint $table) => $table->unique('catalog_id'));
        }

        foreach (self::KEYS as [$table, $column, $parent, $key, $onDelete]) {
            if ($this->hasForeignKey($table, "{$table}_{$column}_foreign")) continue;   // re-run after a partial failure
            Schema::table($table, fn (Blueprint $t) => $t->foreign($column)->references($key)->on($parent)->onDelete($onDelete));
        }
    }

    public function down(): void
    {
        foreach (self::KEYS as [$table, $column]) {
            $name = "{$table}_{$column}_foreign";
            if ($this->hasForeignKey($table, $name)) {
                Schema::table($table, fn (Blueprint $t) => $t->dropForeign($name));
            }
            // MySQL adds an index of the same name when the column had none; drop it too.
            if ($this->hasIndex($table, $name)) {
                Schema::table($table, fn (Blueprint $t) => $t->dropIndex($name));
            }
        }

        Schema::table('view_catalogs', fn (Blueprint $table) => $table->dropUnique(['catalog_id']));
    }

    /** Rows of $table whose $column points at no row of $parent. */
    private function orphans(string $table, string $column, string $parent, string $key)
    {
        return DB::table("$table as c")->whereNotNull("c.$column")
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from("$parent as p")->whereColumn("p.$key", "c.$column"));
    }

    private function hasForeignKey(string $table, string $name): bool
    {
        return DB::table('information_schema.table_constraints')->where('constraint_schema', DB::getDatabaseName())
            ->where('table_name', $table)->where('constraint_name', $name)->where('constraint_type', 'FOREIGN KEY')->exists();
    }

    private function hasIndex(string $table, string $name): bool
    {
        return DB::table('information_schema.statistics')->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)->where('index_name', $name)->exists();
    }
};
