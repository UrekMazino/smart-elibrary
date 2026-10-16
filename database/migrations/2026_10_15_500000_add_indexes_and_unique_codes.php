<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Keys and indexes the application relies on but the database did not have:
 *
 *  - UNIQUE: a copy's barcode (copies.circulation_number); a ticket's reservation and transaction
 *    codes; one reservation and one fine record per ticket; one review per patron per record.
 *  - Foreign keys from the ticket's reservation details and fine record to the ticket, by code.
 *  - Indexes for the date ranges the dashboard counts (views, downloads), the circulation
 *    screens' status filter, and the import's duplicate check by ISBN / ISSN.
 *  - password_resets gets a primary key: Laravel keeps one pending reset per address.
 *  - circulations, circulation_fines and circulation_fine_history lose a UNIQUE index on id that
 *    duplicated their primary key.
 *
 * Changes nothing if a value that must become unique is already used twice, or a code points at
 * no ticket; the message says where.
 */
return new class extends Migration
{
    /** [table, columns] */
    private const UNIQUE = [
        ['copies',                   ['circulation_number']],
        ['circulations',             ['reservation_code']],
        ['circulations',             ['transaction_code']],
        ['circulation_reservations', ['reservation_code']],
        ['circulation_fine_history', ['transaction_code']],
        ['ratings',                  ['user_id', 'catalog_id']],
    ];

    /** [table, column, parent table, parent column] — dependent rows go with their ticket. */
    private const CODE_KEYS = [
        ['circulation_reservations', 'reservation_code', 'circulations', 'reservation_code'],
        ['circulation_fine_history', 'transaction_code', 'circulations', 'transaction_code'],
    ];

    private const INDEXES = [
        ['views', 'created_at'], ['downloads', 'created_at'], ['circulations', 'status'],
        ['view_catalogs', 'isbn'], ['view_catalogs', 'issn'],
    ];

    /** Tables with an index named `id`, UNIQUE on the primary key column. */
    private const REDUNDANT = ['circulations', 'circulation_fines', 'circulation_fine_history'];

    public function up(): void
    {
        $problems = [];
        foreach ([...self::UNIQUE, ['password_resets', ['email']]] as [$table, $columns]) {
            $query = DB::table($table)->select($columns)->groupBy($columns)->havingRaw('count(*) > 1');
            foreach ($columns as $column) $query->whereNotNull($column);
            if ($count = $query->get()->count()) $problems[] = "$table(" . implode(', ', $columns) . ") has $count duplicated value(s)";
        }
        foreach (self::CODE_KEYS as [$table, $column, $parent, $key]) {
            $count = DB::table("$table as c")->whereNotNull("c.$column")
                ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from("$parent as p")->whereColumn("p.$key", "c.$column"))
                ->count();
            if ($count) $problems[] = "$table.$column has $count code(s) with no ticket";
        }
        if ($problems) {
            throw new RuntimeException('Not changed: ' . implode('; ', $problems) . '. Correct those rows, then migrate again.');
        }

        foreach (self::UNIQUE as [$table, $columns]) {
            Schema::table($table, fn (Blueprint $t) => $t->unique($columns));
        }
        foreach (self::CODE_KEYS as [$table, $column, $parent, $key]) {
            Schema::table($table, fn (Blueprint $t) => $t->foreign($column)->references($key)->on($parent)->cascadeOnDelete());
        }
        foreach (self::INDEXES as [$table, $column]) {
            Schema::table($table, fn (Blueprint $t) => $t->index($column));
        }
        Schema::table('password_resets', fn (Blueprint $t) => $t->primary('email'));
        foreach (self::REDUNDANT as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->dropIndex('id'));
        }
    }

    public function down(): void
    {
        foreach (self::REDUNDANT as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->unique('id', 'id'));
        }
        Schema::table('password_resets', fn (Blueprint $t) => $t->dropPrimary());
        foreach (self::INDEXES as [$table, $column]) {
            Schema::table($table, fn (Blueprint $t) => $t->dropIndex([$column]));
        }
        foreach (self::CODE_KEYS as [$table, $column]) {
            Schema::table($table, fn (Blueprint $t) => $t->dropForeign([$column]));
        }
        foreach (array_reverse(self::UNIQUE) as [$table, $columns]) {
            Schema::table($table, fn (Blueprint $t) => $t->dropUnique($columns));
        }
    }
};
