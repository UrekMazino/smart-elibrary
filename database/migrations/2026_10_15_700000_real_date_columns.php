<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Dates that are not bibliographic data become real DATE / TIME columns. Text admitted anything
 * ("next Monday"), sorted '12/06/1990' after '2025-04-10', and could not be compared or validated.
 * Dates inside the MARC record (pub_year, field_data) stay text: catalogue dates such as "[1960?]"
 * or "c1978" are transcriptions, not calendar dates.
 *
 *  - users.birthdate                   was 'Y-m-d' (OPAC profile) or 'm/d/Y' (admin profile)
 *  - circulation_reservations.visit_date  was 'm/d/Y' (the booking date picker)
 *  - circulation_reservations.visit_time  was 'g:i a', e.g. "9:00 am" (the booking time picker)
 *
 * view_catalogs.catalog_date is left alone: it belongs to the old acquisition module, which the
 * legacy cleanup (Batch 3) removes.
 *
 * Every value is parsed against the formats the app has written. Blank values become NULL; any
 * other value that can't be read stops the migration before a column is changed.
 */
return new class extends Migration
{
    /** [table, column, new type, accepted input formats, stored format] */
    private const COLUMNS = [
        ['users',                    'birthdate',  'DATE', ['Y-m-d', 'm/d/Y'],                        'Y-m-d'],
        ['circulation_reservations', 'visit_date', 'DATE', ['Y-m-d', 'm/d/Y'],                        'Y-m-d'],
        ['circulation_reservations', 'visit_time', 'TIME', ['g:i a', 'g:i A', 'H:i', 'H:i:s'],         'H:i:s'],
    ];

    public function up(): void
    {
        $plans = [];
        $bad = [];
        foreach (self::COLUMNS as [$table, $column, , $formats, $out]) {
            $plans[$table . '.' . $column] = [];
            $values = DB::table($table)->whereNotNull($column)->distinct()->pluck($column);
            foreach ($values as $value) {
                if (trim($value) === '') continue;
                $parsed = self::parse(trim($value), $formats, $out);
                if ($parsed === null) $bad[] = "$table.$column \"$value\"";
                else $plans[$table . '.' . $column][$value] = $parsed;
            }
        }
        if ($bad) {
            throw new RuntimeException('Not changed: these values are not dates or times: ' . implode(', ', array_slice($bad, 0, 10))
                . (count($bad) > 10 ? ' (and ' . (count($bad) - 10) . ' more)' : '') . '. Correct them, then migrate again.');
        }

        foreach (self::COLUMNS as [$table, $column, $type]) {
            DB::table($table)->whereRaw("TRIM(`$column`) = ''")->update([$column => null]);
            foreach ($plans[$table . '.' . $column] as $from => $to) {
                DB::table($table)->where($column, $from)->update([$column => $to]);
            }
            DB::statement("ALTER TABLE `$table` MODIFY `$column` $type NULL");
        }
    }

    public function down(): void
    {
        foreach (self::COLUMNS as [$table, $column]) {
            DB::statement("ALTER TABLE `$table` MODIFY `$column` VARCHAR(50) NULL");
        }
    }

    /** The value in $out format, read with the first input format that matches it exactly. */
    private static function parse(string $value, array $formats, string $out): ?string
    {
        foreach ($formats as $format) {
            $date = DateTime::createFromFormat('!' . $format, $value);
            $errors = DateTime::getLastErrors();
            if ($date && (! $errors || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
                return $date->format($out);
            }
        }
        return null;
    }
};
