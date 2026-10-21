<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Convert MARC templates between their STORED shape (marc_templates.fields) and the SOURCE
 * shape the shared MarcEditor (public/js/admin/marc-record-editor.js) loads, so the template
 * editor and the Create/Edit Record pages all render a template through the same editor.
 *
 *   stored : [{id:<tag id>, subfield:[<subfield id>...], default:[<value>...], justify?:[0|1...],
 *              indicator:[<indicator id>|0, <indicator id>|0], required:0|1}, ...]
 *   source : {tag:{id,code,description,isRequired}, subfields:[{id,code,description,default}],
 *             indicators:[{id,indicator}], allSubfields:[...], allIndicators:[[...],[...]], editable:true}
 *
 * Legacy templates carry null "holes" in subfield/default (left by the old editor's Remove
 * Subfield); those are dropped instead of being rendered as "No Subfield" rows. LDR/008 are
 * skipped: the record pages generate them in the Fixed Fields panel.
 */
class MarcTemplateFields
{
    private const CONTROL_TAGS = ['LDR', '008'];

    /** Stored template JSON (or decoded array) -> MarcEditor source fields. */
    public function toSource($stored): array
    {
        $items = $this->decode($stored);
        $tags  = $this->tagOptions(array_column($items, 'id'));

        $fields = [];
        foreach ($items as $item) {
            $tag = $tags[(int) ($item['id'] ?? 0)] ?? null;
            if (! $tag || in_array($tag['code'], self::CONTROL_TAGS, true)) {
                continue;
            }

            $subfieldsById = array_column($tag['subfields'], null, 'id');
            $subfields = [];
            foreach (array_values($item['subfield'] ?? []) as $i => $subId) {
                $s = $subfieldsById[(int) $subId] ?? null;
                if (! $s) {
                    continue;   // a legacy null hole, or a subfield that doesn't belong to this tag
                }
                $subfields[] = $s + [
                    'default' => (string) ($item['default'][$i] ?? ''),
                    'align'   => ! empty($item['justify'][$i]) ? 'justify' : null,
                ];
            }

            $indicators = [];
            foreach ([0, 1] as $pos) {
                $indId = (int) ($item['indicator'][$pos] ?? 0);
                $match = collect($tag['indicators'][$pos])->firstWhere('id', $indId)
                    ?? $this->indicatorById($indId);
                $indicators[] = $match ?: ['id' => 0, 'indicator' => 'None'];
            }

            $fields[] = [
                'tag'           => ['id' => $tag['id'], 'code' => $tag['code'], 'description' => $tag['description'],
                                    'isRequired' => ! empty($item['required']) ? 1 : 0],
                'subfields'     => $subfields,
                'indicators'    => $indicators,
                'allSubfields'  => $tag['subfields'],
                'allIndicators' => $tag['indicators'],
                'editable'      => true,
            ];
        }

        return $fields;
    }

    /**
     * Template editor payload -> clean stored array. Unknown tags, control tags, subfields that
     * don't belong to their tag, and rows with no subfield code chosen are dropped.
     */
    public function sanitize($payload): array
    {
        $items = $this->decode($payload);
        $tags  = $this->tagOptions(array_column($items, 'id'));

        $out = [];
        foreach ($items as $item) {
            $tag = $tags[(int) ($item['id'] ?? 0)] ?? null;
            if (! $tag || in_array($tag['code'], self::CONTROL_TAGS, true)) {
                continue;
            }

            $validSubIds = array_column($tag['subfields'], 'id');
            $subfield = [];
            $default  = [];
            $justify  = [];
            foreach (array_values($item['subfield'] ?? []) as $i => $subId) {
                if (! in_array((int) $subId, $validSubIds, true)) {
                    continue;
                }
                $subfield[] = (int) $subId;
                $default[]  = rtrim((string) ($item['default'][$i] ?? ''));   // keep leading indentation
                $justify[]  = ! empty($item['justify'][$i]) ? 1 : 0;
            }

            $indicator = [];
            foreach ([0, 1] as $pos) {
                $indId = (int) ($item['indicator'][$pos] ?? 0);
                $indicator[] = in_array($indId, array_column($tag['indicators'][$pos], 'id'), true) ? $indId : 0;
            }

            $row = [
                'id'        => $tag['id'],
                'subfield'  => $subfield,
                'default'   => $default,
                'indicator' => $indicator,
                'required'  => ! empty($item['required']) ? 1 : 0,
            ];
            if (in_array(1, $justify, true)) {
                $row['justify'] = $justify;   // per-default-value Justify flags (only stored when used)
            }
            $out[] = $row;
        }

        return $out;
    }

    /**
     * Picker options per tag id, loaded in three queries:
     *   [tagId => [id, code, description, subfields:[{id,code,description}], indicators:[[first], [second]]]]
     */
    public function tagOptions(array $tagIds): array
    {
        $tagIds = array_values(array_unique(array_filter(array_map('intval', $tagIds))));
        if (! $tagIds) {
            return [];
        }

        $tags = DB::table('marctags_loc')->whereIn('id', $tagIds)
            ->get(['id', 'code', 'description', 'first_indictors', 'second_indicators']);

        $subfields = DB::table('marctag_subfields_loc')->whereIn('marctags_loc_id', $tagIds)
            ->orderBy('code')->get(['id', 'code', 'description', 'marctags_loc_id'])
            ->groupBy('marctags_loc_id');

        $indIdsByTag = [];
        foreach ($tags as $t) {
            $indIdsByTag[$t->id] = [
                array_map('intval', json_decode($t->first_indictors ?: '[]', true) ?: []),
                array_map('intval', json_decode($t->second_indicators ?: '[]', true) ?: []),
            ];
        }
        $indicators = DB::table('marctag_indicators_loc')
            ->whereIn('id', array_merge([], ...array_merge([], ...array_values($indIdsByTag))))
            ->get(['id', 'indicator'])->keyBy('id');

        $out = [];
        foreach ($tags as $t) {
            $out[$t->id] = [
                'id'          => $t->id,
                'code'        => $t->code,
                'description' => $t->description,
                'subfields'   => ($subfields[$t->id] ?? collect())
                    ->map(fn ($s) => ['id' => $s->id, 'code' => $s->code, 'description' => $s->description])
                    ->values()->all(),
                'indicators'  => array_map(fn ($ids) => array_values(array_filter(array_map(
                    fn ($id) => isset($indicators[$id]) ? ['id' => $id, 'indicator' => $indicators[$id]->indicator] : null,
                    $ids
                ))), $indIdsByTag[$t->id]),
            ];
        }

        return $out;
    }

    private function indicatorById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $ind = DB::table('marctag_indicators_loc')->where('id', $id)->first(['id', 'indicator']);

        return $ind ? ['id' => $ind->id, 'indicator' => $ind->indicator] : null;
    }

    private function decode($value): array
    {
        $arr = is_array($value) ? $value : json_decode((string) $value ?: '[]', true);

        return is_array($arr) ? array_values(array_filter($arr, 'is_array')) : [];
    }
}
