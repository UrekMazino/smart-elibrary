<?php

namespace App\Services;

use File_MARC_Data_Field;
use File_MARC_Record;
use Illuminate\Support\Facades\DB;
use Scriptotek\Marc\Collection;

/**
 * Batch Import material-type mapping.
 *
 * analyze()     — scans an uploaded MARC file and lists the fields whose values could identify
 *                 a record's material type (e.g. Koha's 942$c: FN / TH / AR), with each field's
 *                 distinct values and how many records carry them.
 * materialFor() — during the import, the Material Type a record maps to (or null → the
 *                 batch's default type).
 *
 * Fields are written "TAG$code" for data-field subfields and "LDR/06" for leader positions.
 * Matching is exact but ignores case and spacing (normalize()).
 */
class MarcMaterialMapper
{
    /** Fields that usually say what kind of material a record is — offered first, with a label. */
    public const SUGGESTED = [
        '942$c'  => 'Koha item type',
        '952$y'  => 'Koha item type (per copy)',
        '336$a'  => 'Content type',
        '338$a'  => 'Carrier type',
        '245$h'  => 'Medium (GMD)',
        '655$a'  => 'Genre / form',
        '502$a'  => 'Dissertation note',
        'LDR/06' => 'Type of record',
        'LDR/07' => 'Bibliographic level',
    ];

    /** A field with more distinct values than this isn't a category (titles, ISBNs, ...). */
    private const MAX_VALUES = 60;

    public static function normalize($value): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string) $value)));
    }

    /** @return array{total:int, fields:array<int, array>} */
    public function analyze(string $path): array
    {
        $stats = [];   // field => ['records' => n, 'values' => [norm => [display, count]], 'overflow' => bool, 'repeats' => bool]
        $total = 0;

        foreach (Collection::fromFile($path) as $wrapped) {
            $record = $wrapped->record;
            if (! $record instanceof File_MARC_Record) continue;
            $total++;

            foreach ($this->fieldValues($record) as $field => $values) {
                $s = $stats[$field] ?? ['records' => 0, 'values' => [], 'overflow' => false, 'repeats' => false];
                $s['records']++;
                $distinct = [];
                foreach ($values as $v) {
                    $n = self::normalize($v);
                    if ($n === '' || isset($distinct[$n])) continue;
                    $distinct[$n] = true;
                    if (isset($s['values'][$n])) {
                        $s['values'][$n][1]++;
                    } elseif (! $s['overflow']) {
                        if (count($s['values']) >= self::MAX_VALUES) { $s['overflow'] = true; $s['values'] = []; }
                        else { $s['values'][$n] = [mb_substr(trim(preg_replace('/\s+/u', ' ', $v)), 0, 120), 1]; }
                    }
                }
                if (count($distinct) > 1) $s['repeats'] = true;   // a record carries several values
                $stats[$field] = $s;
            }
        }

        $labels = $this->labels(array_keys($stats));
        $fields = [];
        foreach ($stats as $field => $s) {
            if ($s['overflow'] || ! $s['values']) continue;
            $values = array_map(fn ($norm, $v) => ['value' => $v[0], 'key' => $norm, 'count' => $v[1]], array_keys($s['values']), $s['values']);
            usort($values, fn ($a, $b) => $b['count'] <=> $a['count'] ?: strcmp($a['value'], $b['value']));
            $fields[] = [
                'field'     => $field,
                'label'     => $labels[$field] ?? '',
                'suggested' => isset(self::SUGGESTED[$field]),
                'records'   => $s['records'],
                'repeats'   => $s['repeats'],
                'values'    => $values,
            ];
        }

        // Suggested fields first (in SUGGESTED order), then the most useful-looking others:
        // present in more records, with fewer distinct values.
        $order = array_flip(array_keys(self::SUGGESTED));
        usort($fields, function ($a, $b) use ($order) {
            $ia = $order[$a['field']] ?? PHP_INT_MAX; $ib = $order[$b['field']] ?? PHP_INT_MAX;
            // A field with one value for every record can't tell records apart: list it last.
            $sa = count($a['values']) > 1 ? 0 : 1; $sb = count($b['values']) > 1 ? 0 : 1;
            return [$ia, $sa, -$a['records'], count($a['values']), $a['field']] <=> [$ib, $sb, -$b['records'], count($b['values']), $b['field']];
        });

        return ['total' => $total, 'fields' => $fields];
    }

    /**
     * Material Type id for one record, or null when the record lacks the field or none of its
     * values is mapped (the caller then uses the batch's default type).
     *
     * @param array<string,int> $map normalised value => material id
     */
    public function materialFor(File_MARC_Record $record, ?string $field, array $map): ?int
    {
        if (! $field || ! $map) return null;

        foreach ($this->valuesOf($record, $field) as $v) {
            $n = self::normalize($v);
            if ($n !== '' && isset($map[$n])) return (int) $map[$n];
        }
        return null;
    }

    /** All values of one field in a record ("942$c" → every 942 $c; "LDR/06" → that position). */
    public function valuesOf(File_MARC_Record $record, string $field): array
    {
        if (preg_match('#^LDR/(\d{2})$#', $field, $m)) {
            return [substr((string) $record->getLeader(), (int) $m[1], 1)];
        }
        if (! preg_match('/^(\d{3})\$([a-z0-9])$/i', $field, $m)) return [];

        $out = [];
        foreach ($record->getFields($m[1]) as $f) {
            if (! $f instanceof File_MARC_Data_Field) continue;
            foreach ($f->getSubfields($m[2]) as $sf) $out[] = (string) $sf->getData();
        }
        return $out;
    }

    /** Every candidate field in a record with its values: leader positions + data subfields. */
    private function fieldValues(File_MARC_Record $record): array
    {
        $ldr = (string) $record->getLeader();
        $out = ['LDR/06' => [substr($ldr, 6, 1)], 'LDR/07' => [substr($ldr, 7, 1)]];

        foreach ($record->getFields() as $f) {
            if (! $f instanceof File_MARC_Data_Field) continue;   // 00X control fields: no subfields
            foreach ($f->getSubfields() as $sf) {
                $out[$f->getTag() . '$' . $sf->getCode()][] = (string) $sf->getData();
            }
        }
        return $out;
    }

    /** "245$h" → "Title Statement — Medium"; suggested fields use their own short label. */
    private function labels(array $fields): array
    {
        $labels = [];
        $codes = [];
        foreach ($fields as $field) {
            if (isset(self::SUGGESTED[$field])) { $labels[$field] = self::SUGGESTED[$field]; continue; }
            if (preg_match('/^(\d{3})\$/', $field, $m)) $codes[$m[1]] = true;
        }
        if ($codes) {
            $tags = DB::table('marctags_loc')->where('id', '<>', MARCTAG_NOT_FOUND_ID)->whereIn('code', array_keys($codes))->pluck('description', 'code');
            $subs = DB::table('marctag_subfields_loc')->whereIn('code', array_diff($fields, array_keys($labels)))->pluck('description', 'code');
            foreach ($fields as $field) {
                if (isset($labels[$field])) continue;
                $tag = substr($field, 0, 3);
                $labels[$field] = trim(($tags[$tag] ?? '') . (isset($subs[$field]) ? ' — ' . $subs[$field] : ''), ' —');
            }
        }
        return $labels;
    }
}
