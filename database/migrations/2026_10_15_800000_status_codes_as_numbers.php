<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Status codes become small numbers tied to their vocabularies:
 *
 *  - copies.status (CHAR(50) holding digits) and circulations.status (INT) become TINYINT UNSIGNED
 *    with a foreign key to copies_status.order_no / circulation_status.order_no, which become
 *    TINYINT UNSIGNED and UNIQUE (copies_status.order_no was text). A copy without a status gets
 *    4 (Not Available), the column's existing default.
 *  - reservation_messages.sender_role ('admin' / 'patron') becomes an ENUM.
 *  - copies.location is a free-text shelf location, not a code: CHAR(50) with a meaningless
 *    default '1' becomes VARCHAR(100), no default.
 *
 * Changes nothing if a status is not a number or not in its vocabulary, or a role is unknown.
 */
return new class extends Migration
{
    /** [table, column, lookup table, default] */
    private const STATUSES = [
        ['copies',       'status', 'copies_status',      4],
        ['circulations', 'status', 'circulation_status', 1],
    ];

    public function up(): void
    {
        $problems = [];
        foreach (self::STATUSES as [$table, $column, $lookup]) {
            $codes = DB::table($lookup)->pluck('order_no');
            if ($codes->contains(fn ($c) => ! ctype_digit((string) $c) || (int) $c > 255)) $problems[] = "$lookup.order_no has a value that is not a number from 0 to 255";
            if ($codes->map(fn ($c) => (int) $c)->duplicates()->isNotEmpty()) $problems[] = "$lookup.order_no has duplicates";

            $unknown = DB::table($table)->whereNotNull($column)
                ->whereNotIn(DB::raw("TRIM(`$column`)"), $codes->map(fn ($c) => (string) (int) $c)->all() ?: ['-'])
                ->count();
            if ($unknown) $problems[] = "$table.$column has $unknown value(s) not in $lookup";
        }
        $roles = DB::table('reservation_messages')->whereNotIn('sender_role', ['admin', 'patron'])->count();
        if ($roles) $problems[] = "reservation_messages.sender_role has $roles value(s) other than admin / patron";
        if ($problems) {
            throw new RuntimeException('Not changed: ' . implode('; ', $problems) . '. Correct those rows, then migrate again.');
        }

        Schema::table('circulation_status', fn (Blueprint $t) => $t->dropIndex('order_no'));
        foreach (self::STATUSES as [$table, $column, $lookup, $default]) {
            DB::statement("ALTER TABLE `$lookup` MODIFY `order_no` TINYINT UNSIGNED NOT NULL");
            Schema::table($lookup, fn (Blueprint $t) => $t->unique('order_no'));

            DB::table($table)->whereNull($column)->update([$column => $default]);
            DB::statement("ALTER TABLE `$table` MODIFY `$column` TINYINT UNSIGNED NOT NULL DEFAULT $default");
            Schema::table($table, fn (Blueprint $t) => $t->foreign($column)->references('order_no')->on($lookup)->restrictOnDelete()->cascadeOnUpdate());
        }

        DB::statement("ALTER TABLE `reservation_messages` MODIFY `sender_role` ENUM('admin', 'patron') NOT NULL DEFAULT 'admin'");
        DB::statement('ALTER TABLE `copies` MODIFY `location` VARCHAR(100) NULL');
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `copies` MODIFY `location` CHAR(50) NULL DEFAULT '1'");
        DB::statement("ALTER TABLE `reservation_messages` MODIFY `sender_role` VARCHAR(20) NOT NULL DEFAULT 'admin'");

        // Each step checks first, so a rollback that stopped halfway can be run again. (The key on
        // circulations.status uses the existing circulations_status_index, so it has no index of
        // its own.)
        foreach (self::STATUSES as [$table, $column, $lookup]) {
            $key = "{$table}_{$column}_foreign";
            if ($this->hasForeignKey($table, $key)) Schema::table($table, fn (Blueprint $t) => $t->dropForeign($key));
            if ($this->hasIndex($table, $key)) Schema::table($table, fn (Blueprint $t) => $t->dropIndex($key));
            if ($this->hasIndex($lookup, "{$lookup}_order_no_unique")) Schema::table($lookup, fn (Blueprint $t) => $t->dropUnique(['order_no']));
        }
        DB::statement("ALTER TABLE `copies` MODIFY `status` CHAR(50) NULL DEFAULT '4'");
        DB::statement("ALTER TABLE `circulations` MODIFY `status` INT NOT NULL DEFAULT 1");
        DB::statement('ALTER TABLE `copies_status` MODIFY `order_no` VARCHAR(50) NULL');
        DB::statement('ALTER TABLE `circulation_status` MODIFY `order_no` INT NULL');
        if (! $this->hasIndex('circulation_status', 'order_no')) {
            Schema::table('circulation_status', fn (Blueprint $t) => $t->index('order_no', 'order_no'));
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
