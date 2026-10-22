<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * MARC 21 leaves 900–999 to each library, so the LoC tables have none of them but 901, and Add Tag
 * (Create New Record, Edit, MARC Templates) could not offer the local tags Koha records carry (942
 * item type, 952 holdings, 999 ids). Each gets a row like 901's: named by its number (a local tag
 * has no standard name), indicators "# - Undefined", subfields $0–$9 and $a–$z named by their code.
 *
 * Fixed ids (tags 2502–2600, subfields 2634–6197), so every install numbers them alike;
 * database/seeders/data/marctags_loc.json and marctag_subfields_loc.json carry the same rows, and a
 * fresh database (empty tables, filled by the seeder) is left to it. Records that held these tags
 * under the "Marctag not found" placeholder (id 2500) are pointed at the new rows.
 */
return new class extends Migration
{
    private const FIRST_TAG_ID = 2502;
    private const FIRST_SUBFIELD_ID = 2634;
    private const UNDEFINED_INDICATOR = '[1]';   // marctag_indicators_loc 1: "# - Undefined"

    /** The local tags: 900 and 902–999 (901 is in the LoC data already). */
    public static function codes(): array
    {
        return array_values(array_diff(array_map(fn ($n) => (string) $n, range(900, 999)), ['901']));
    }

    /** @return array{0: array, 1: array} the marctags_loc and marctag_subfields_loc rows */
    public static function rows(): array
    {
        $subCodes = array_merge(array_map('strval', range(0, 9)), range('a', 'z'));
        $tags = $subs = [];
        foreach (self::codes() as $i => $code) {
            $tagId = self::FIRST_TAG_ID + $i;
            $tags[] = ['id' => $tagId, 'code' => $code, 'description' => $code, 'first_indictors' => self::UNDEFINED_INDICATOR,
                'second_indicators' => self::UNDEFINED_INDICATOR, 'Repeatable' => 1, 'created_at' => null, 'updated_at' => null];
            foreach ($subCodes as $j => $sub) {
                $subs[] = ['id' => self::FIRST_SUBFIELD_ID + $i * count($subCodes) + $j, 'marctags_loc_id' => $tagId,
                    'code' => "$code\$$sub", 'description' => "$code\$$sub", 'repeatable' => 1, 'created_at' => null, 'updated_at' => null];
            }
        }
        return [$tags, $subs];
    }

    public function up(): void
    {
        if (DB::table('marctags_loc')->doesntExist()) return;   // fresh database: the seeder loads them

        [$tags, $subs] = self::rows();
        $have = DB::table('marctags_loc')->where('id', '<>', MARCTAG_NOT_FOUND_ID)->whereIn('code', self::codes())->pluck('code')->all();
        $tags = array_values(array_filter($tags, fn ($t) => ! in_array($t['code'], $have, true)));
        $tagIds = array_column($tags, 'id');
        $subs = array_values(array_filter($subs, fn ($s) => in_array($s['marctags_loc_id'], $tagIds, true)));

        $taken = DB::table('marctags_loc')->whereIn('id', $tagIds)->pluck('id')
            ->merge(DB::table('marctag_subfields_loc')->whereIn('id', array_column($subs, 'id'))->pluck('id'));
        if ($taken->isNotEmpty()) {
            throw new RuntimeException('Not changed: the ids for the local tags are in use (' . $taken->take(10)->implode(', ') . ').');
        }

        DB::transaction(function () use ($tags, $subs) {
            DB::table('marctags_loc')->insert($tags);
            foreach (array_chunk($subs, 500) as $chunk) DB::table('marctag_subfields_loc')->insert($chunk);
            $this->repoint(MARCTAG_NOT_FOUND_ID, true);
        });
    }

    public function down(): void
    {
        [$tags, $subs] = self::rows();
        DB::transaction(function () use ($tags, $subs) {
            $this->repoint(MARCTAG_NOT_FOUND_ID, false);
            DB::table('marctag_subfields_loc')->whereIn('id', array_column($subs, 'id'))->delete();
            DB::table('marctags_loc')->whereIn('id', array_column($tags, 'id'))->delete();
        });
    }

    /** Records' 9XX fields: from the placeholder to their own rows ($up), or back. Values untouched. */
    private function repoint(int $placeholder, bool $up): void
    {
        $tagId = DB::table('marctags_loc')->where('id', '<>', $placeholder)->whereIn('code', self::codes())->pluck('id', 'code');
        $subId = DB::table('marctag_subfields_loc')->whereIn('marctags_loc_id', $tagId->values())->pluck('id', 'code');

        DB::table('catalogs')->whereNotNull('field_data')->select('id', 'field_data')->orderBy('id')
            ->chunkById(200, function ($catalogs) use ($placeholder, $up, $tagId, $subId) {
                foreach ($catalogs as $catalog) {
                    $fields = json_decode($catalog->field_data, true);
                    if (! is_array($fields)) continue;

                    $changed = false;
                    foreach ($fields as &$field) {
                        $code = (string) ($field['marctag']['code'] ?? '');
                        if (! isset($tagId[$code])) continue;
                        $from = $up ? $placeholder : $tagId[$code];
                        if ((int) ($field['marctag']['id'] ?? 0) !== $from) continue;

                        $field['marctag']['id'] = $up ? $tagId[$code] : $placeholder;
                        foreach ($field['subfield'] ?? [] as $i => $sub) {
                            $field['subfield'][$i]['id'] = $up ? ($subId[$sub['code'] ?? ''] ?? null) : null;
                        }
                        $changed = true;
                    }
                    unset($field);

                    if ($changed) {
                        DB::table('catalogs')->where('id', $catalog->id)->update(['field_data' => json_encode($fields)]);
                    }
                }
            });
    }
};
