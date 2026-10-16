<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Fine amounts were VARCHAR(50): text admits values that are not numbers, sorts '100' before '20',
 * and leaves arithmetic to the application. Store money as DECIMAL(10,2) and the overdue hour count
 * as an integer.
 *
 * Blank values, and the 'NaN' the check-in screen posts when an amount box is left empty, become
 * NULL. Anything else that is not a plain number stops the migration before a column is changed,
 * because MySQL (non-strict here) would otherwise store it as 0 without complaint.
 */
return new class extends Migration
{
    /** [table, column, new definition] — every column was VARCHAR(50) NULL. */
    private const COLUMNS = [
        ['circulations',             'overdue_fine_rate',   'DECIMAL(10,2) NULL'],
        ['circulations',             'waived_amount',       'DECIMAL(10,2) NULL'],
        ['circulations',             'total_fine',          'DECIMAL(10,2) NULL'],
        ['circulations',             'total_fine_paid',     'DECIMAL(10,2) NULL'],
        ['circulation_fine_history', 'overdue_fine_amount', 'DECIMAL(10,2) NULL'],
        ['circulation_fine_history', 'other_fine_amount',   'DECIMAL(10,2) NULL'],
        ['circulation_fine_history', 'overdue_total_hours', 'INT UNSIGNED NULL'],
    ];

    private const BLANK = "TRIM(`%s`) IN ('', 'NaN', 'undefined', 'null')";

    public function up(): void
    {
        $bad = [];
        foreach (self::COLUMNS as [$table, $column, $type]) {
            $pattern = str_starts_with($type, 'DECIMAL') ? '^-?[0-9]+(\\.[0-9]+)?$' : '^[0-9]+$';
            $count = DB::table($table)->whereNotNull($column)
                ->whereRaw('NOT ' . sprintf(self::BLANK, $column))
                ->whereRaw("TRIM(`$column`) NOT REGEXP ?", [$pattern])
                ->count();
            if ($count) $bad[] = "$table.$column ($count)";
        }
        if ($bad) {
            throw new RuntimeException('Not changed: these columns hold values that are not plain numbers: ' . implode(', ', $bad)
                . '. Correct those rows (no currency signs or thousands separators), then migrate again.');
        }

        foreach (self::COLUMNS as [$table, $column, $type]) {
            DB::table($table)->whereRaw(sprintf(self::BLANK, $column))->update([$column => null]);
            DB::statement("ALTER TABLE `$table` MODIFY `$column` $type");
        }
    }

    public function down(): void
    {
        foreach (self::COLUMNS as [$table, $column]) {
            DB::statement("ALTER TABLE `$table` MODIFY `$column` VARCHAR(50) NULL");
        }
    }
};
