<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tags outside MARC 21 (local ones, such as Koha's 942 item type) were imported with the "Marctag
 * not found" placeholder's id as their name (2500, shown as the label "2500" on the record page), a
 * 999 with the placeholder's own name, and their subfields with "MARCTAG (942$c) not found!" as id
 * and name. A local tag is now named by its number and its subfields by their codes (getTagDetails,
 * getSubfield); this rewrites the records imported before. Only those names change, never a value,
 * and updated_at is left alone.
 *
 * Not recoverable here: the import kept only the first local tag of a record (all of them shared the
 * placeholder id), so a 952 or 999 after a 942 was dropped. Re-import a record to get those back.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('catalogs')->whereNotNull('field_data')->select('id', 'field_data')->orderBy('id')
            ->chunkById(200, function ($catalogs) {
                foreach ($catalogs as $catalog) {
                    $fields = json_decode($catalog->field_data, true);
                    if (! is_array($fields)) continue;

                    $changed = false;
                    foreach ($fields as &$field) {
                        $tag = $field['marctag'] ?? null;
                        if (! is_array($tag) || ! isset($tag['code'])) continue;

                        $local = (int) ($tag['id'] ?? 0) === MARCTAG_NOT_FOUND_ID;
                        $name  = $tag['description'] ?? null;
                        if (($local || ! is_string($name)) && $name !== (string) $tag['code']) {
                            $field['marctag']['description'] = (string) $tag['code'];
                            $changed = true;
                        }

                        foreach ($field['subfield'] ?? [] as $i => $sub) {
                            if (is_string($sub['id'] ?? null) && str_starts_with($sub['id'], 'MARCTAG (')) {
                                $field['subfield'][$i]['id'] = null;
                                $changed = true;
                            }
                            if (is_string($sub['description'] ?? null) && str_starts_with($sub['description'], 'MARCTAG (') && isset($sub['code'])) {
                                $field['subfield'][$i]['description'] = $sub['code'];
                                $changed = true;
                            }
                        }
                    }
                    unset($field);

                    if ($changed) {
                        DB::table('catalogs')->where('id', $catalog->id)->update(['field_data' => json_encode($fields)]);
                    }
                }
            });
    }

    public function down(): void
    {
        // Nothing to undo: the old names were the placeholder's id and an error message.
    }
};
