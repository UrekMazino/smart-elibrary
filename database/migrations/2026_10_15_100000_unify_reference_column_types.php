<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Give every column that points at a row the same type as the key it points at, so the database
 * can enforce the link (next migration: foreign keys) and joins compare like with like.
 *
 *  - Catalogue references were stored as VARCHAR(24/25/50/100), INT, BIGINT and TINYINT; they all
 *    become INT UNSIGNED, the type of catalogs.id. catalog_import_id was a signed TINYINT, which
 *    capped imports at batch 127 (MySQL here runs non-strict, so batch 128 was silently stored
 *    as 127).
 *  - User references (and the created_by / updated_by / deleted_by audit columns, which hold user
 *    ids — catalogs kept them as text) become BIGINT UNSIGNED, the type of users.id.
 *  - materials.id becomes INT UNSIGNED like the catalogue's material_type_id.
 *  - users.patrontypes_id becomes nullable: accounts without a patron type (staff, and every
 *    self-registered patron until they pick one) had 0, a value no patron type has.
 *  - holdingscopypresenteds.circulation_number takes the collation of copies.circulation_number
 *    (utf8mb4_general_ci, not utf8mb4_unicode_ci): the inventory reports join the two, and MySQL
 *    refuses "=" between columns of different collations.
 *
 * Empty strings and 0 in nullable reference columns mean "none" and become NULL. Anything else
 * that is not a whole number stops the migration before a column is changed, because MySQL
 * would otherwise turn it into 0 without complaint.
 */
return new class extends Migration
{
    /** [table, column, new definition, original definition] */
    private const COLUMNS = [
        // Catalogue references -> catalogs.id
        ['copies',                   'catalog_id',        'INT UNSIGNED NULL',        'VARCHAR(50) NULL'],
        ['multimedia',               'catalog_id',        'INT UNSIGNED NULL',        'VARCHAR(24) NULL'],
        ['frontpages',               'catalog_id',        'INT UNSIGNED NOT NULL',    'VARCHAR(24) NOT NULL'],
        ['views',                    'catalog_id',        'INT UNSIGNED NULL',        'VARCHAR(50) NULL'],
        ['downloads',                'catalog_id',        'INT UNSIGNED NULL',        'VARCHAR(50) NULL'],
        ['reads',                    'catalog_id',        'INT UNSIGNED NOT NULL',    'BIGINT UNSIGNED NOT NULL'],
        ['ratings',                  'catalog_id',        'INT UNSIGNED NULL',        'VARCHAR(25) NULL'],
        ['inquiries',                'catalog_id',        'INT UNSIGNED NULL',        'VARCHAR(50) NULL'],
        ['circulations',             'catalog_id',        'INT UNSIGNED NULL',        'INT NULL'],
        ['holdingscopypresenteds',   'catalog_id',        'INT UNSIGNED NULL',        'VARCHAR(100) NULL'],
        ['rag_sync_queue',           'catalog_id',        'INT UNSIGNED NOT NULL',    'BIGINT UNSIGNED NOT NULL'],
        ['view_catalogs',            'catalog_id',        'INT UNSIGNED NOT NULL',    "INT UNSIGNED NOT NULL DEFAULT '0'"],
        // Import batch -> catalog_imports.id
        ['catalogs',                 'catalog_import_id', 'INT UNSIGNED NULL',        'TINYINT NULL'],
        ['view_catalogs',            'catalog_import_id', 'INT UNSIGNED NULL',        'TINYINT NULL'],
        // Material type -> materials.id
        ['materials',                'id',                'INT UNSIGNED NOT NULL AUTO_INCREMENT', 'INT NOT NULL AUTO_INCREMENT'],
        ['view_catalogs',            'material_type_id',  'INT UNSIGNED NULL',        'INT NULL'],
        ['catalog_imports',          'material_type_id',  "INT UNSIGNED NULL DEFAULT '21'", "INT NULL DEFAULT '21'"],
        // User references -> users.id
        ['views',                    'user_id',           'BIGINT UNSIGNED NULL',     'INT NULL'],
        ['downloads',                'user_id',           'BIGINT UNSIGNED NULL',     'INT NULL'],
        ['ratings',                  'user_id',           'BIGINT UNSIGNED NULL',     'INT NULL'],
        ['circulations',             'user_id',           'BIGINT UNSIGNED NULL',     'INT NULL'],
        ['circulation_reservations', 'user_id',           'BIGINT UNSIGNED NULL',     'INT NULL'],
        ['inquiries',                'user_id',           'BIGINT UNSIGNED NULL',     'INT NULL'],
        ['reply_inquiries',          'user_id',           'BIGINT UNSIGNED NULL',     'INT NULL'],
        ['catalogs',                 'created_by',        'BIGINT UNSIGNED NULL',     'VARCHAR(100) NULL'],
        ['catalogs',                 'updated_by',        'BIGINT UNSIGNED NULL',     'VARCHAR(100) NULL'],
        ['catalogs',                 'deleted_by',        'BIGINT UNSIGNED NULL',     'VARCHAR(100) NULL'],
        ['view_catalogs',            'created_by',        'BIGINT UNSIGNED NULL',     'INT NULL'],
        ['view_catalogs',            'updated_by',        'BIGINT UNSIGNED NULL',     'INT NULL'],
        ['view_catalogs',            'deleted_by',        'BIGINT UNSIGNED NULL',     'INT NULL'],
        ['catalog_imports',          'created_by',        'BIGINT UNSIGNED NULL',     'VARCHAR(50) NULL'],
        ['catalog_imports',          'updated_by',        'BIGINT UNSIGNED NULL',     'VARCHAR(50) NULL'],
        ['catalog_imports',          'deleted_by',        'BIGINT UNSIGNED NULL',     'VARCHAR(50) NULL'],
        ['users',                    'patrontypes_id',    'BIGINT UNSIGNED NULL',     'BIGINT UNSIGNED NOT NULL'],
        // Other links
        ['reply_inquiries',          'inquiry_id',        'BIGINT UNSIGNED NULL',     'INT NULL'],   // -> inquiries.id
        ['circulations',             'copy_id',           'BIGINT UNSIGNED NULL',     'INT NULL'],   // -> copies.id
    ];

    public function up(): void
    {
        $bad = [];
        foreach (self::COLUMNS as [$table, $column, $type]) {
            $nullable = ! str_contains($type, 'NOT NULL');
            $count = DB::table($table)->whereNotNull($column)
                ->when($nullable, fn ($q) => $q->whereRaw("TRIM(`$column`) NOT IN ('', '0')"))
                ->whereRaw("CAST(`$column` AS CHAR) NOT REGEXP '^[0-9]+$'")
                ->count();
            if ($count) $bad[] = "$table.$column ($count)";
        }
        if ($bad) {
            throw new RuntimeException('Not changed: these columns hold values that are not ids: ' . implode(', ', $bad)
                . '. Correct or clear those rows, then migrate again.');
        }

        foreach (self::COLUMNS as [$table, $column, $type]) {
            $nullable = ! str_contains($type, 'NOT NULL');
            if ($nullable) DB::table($table)->whereRaw("TRIM(`$column`) = ''")->update([$column => null]);
            DB::statement("ALTER TABLE `$table` MODIFY `$column` $type");
            // After the change: a column that was NOT NULL (patrontypes_id) can only now hold NULL.
            if ($nullable) DB::table($table)->where($column, 0)->update([$column => null]);
        }

        DB::statement('ALTER TABLE `holdingscopypresenteds` MODIFY `circulation_number` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE `holdingscopypresenteds` MODIFY `circulation_number` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL');
        DB::table('users')->whereNull('patrontypes_id')->update(['patrontypes_id' => 0]);

        foreach (array_reverse(self::COLUMNS) as [$table, $column, , $original]) {
            DB::statement("ALTER TABLE `$table` MODIFY `$column` $original");
        }
    }
};
