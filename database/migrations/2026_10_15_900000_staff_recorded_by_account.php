<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Who approved, declined, released or received a loan, and who wrote a news article, was stored as
 * the staff member's NAME. Names are neither unique nor stable, so the trail could not be joined to
 * accounts and changed meaning when someone was renamed. These columns now hold the user id, like
 * every other *_by column (catalogs.created_by and so on), with a foreign key to users; screens
 * look the name up (staffName()). Deleting an account keeps the record and empties the column.
 *
 * Each stored name is matched to the one account with that name (deleted accounts included).
 * Changes nothing if a name matches no account or several.
 */
return new class extends Migration
{
    /** [table, column] — each was VARCHAR(50) NULL. */
    private const COLUMNS = [
        ['circulations', 'approved_by'],
        ['circulations', 'declined_by'],
        ['circulations', 'released_by'],
        ['circulations', 'received_by'],
        ['news',         'created_by'],
    ];

    public function up(): void
    {
        $ids = [];
        $problems = [];
        foreach (self::COLUMNS as [$table, $column]) {
            $names = DB::table($table)->whereNotNull($column)->whereRaw("TRIM(`$column`) <> ''")->distinct()->pluck($column);
            foreach ($names as $name) {
                $matches = DB::table('users')->where('name', trim($name))->pluck('id');
                if ($matches->count() === 1) $ids[$table . '.' . $column][$name] = $matches->first();
                else $problems[] = "$table.$column \"$name\" matches " . ($matches->count() ? $matches->count() . ' accounts' : 'no account');
            }
        }
        if ($problems) {
            throw new RuntimeException('Not changed: ' . implode('; ', $problems) . '. Rename or merge those accounts, then migrate again.');
        }

        foreach (self::COLUMNS as [$table, $column]) {
            DB::table($table)->whereRaw("TRIM(`$column`) = ''")->update([$column => null]);
            foreach ($ids[$table . '.' . $column] ?? [] as $name => $id) {
                DB::table($table)->where($column, $name)->update([$column => (string) $id]);
            }
            DB::statement("ALTER TABLE `$table` MODIFY `$column` BIGINT UNSIGNED NULL");
            Schema::table($table, fn (Blueprint $t) => $t->foreign($column)->references('id')->on('users')->nullOnDelete());
        }
    }

    public function down(): void
    {
        foreach (self::COLUMNS as [$table, $column]) {
            $key = "{$table}_{$column}_foreign";
            if ($this->hasForeignKey($table, $key)) Schema::table($table, fn (Blueprint $t) => $t->dropForeign($key));
            if ($this->hasIndex($table, $key)) Schema::table($table, fn (Blueprint $t) => $t->dropIndex($key));
            DB::statement("ALTER TABLE `$table` MODIFY `$column` VARCHAR(50) NULL");
            foreach (DB::table($table)->whereNotNull($column)->distinct()->pluck($column) as $id) {
                if (! ctype_digit((string) $id)) continue;   // already a name (a re-run after a partial rollback)
                DB::table($table)->where($column, $id)->update([$column => DB::table('users')->where('id', $id)->value('name')]);
            }
        }
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
