<?php

namespace App\Services;

use App\Models\Catalog;
use File_MARC_Control_Field;
use File_MARC_Data_Field;
use File_MARC_Record;
use File_MARC_Subfield;
use XMLWriter;

/**
 * Convert a catalog record's stored field_data into a proper MARC 21 record and serialize it
 * to MARC21 binary (ISO 2709, .mrc) and MARCXML — for single-record and batch export.
 *
 * field_data is the shape MarcEditor.serialize() writes / storeRecord + updateRecord parse:
 *   [{ marctag:{code,...}, indicator:[{indicator},{indicator}], subfield:[{code,value}] }, ...]
 *
 * Indicators are NORMALISED here at export time (blank -> a real space, 0x20), because legacy
 * records may still carry the 'N' placeholder and the batch-import path bypasses the editor's
 * normaliser. Mirrors marcIndicatorChar() in public/js/admin/marc-record-editor.js.
 */
class MarcExportService
{
    /** 24-char leader template; File_MARC recomputes 00-04 (length) and 12-16 (base) on toRaw(). */
    private const LEADER_TEMPLATE = '00000nam a2200000 a 4500';

    /** Build a File_MARC_Record from a catalog's field_data (null field_data -> empty record). */
    public function buildRecord(Catalog $catalog): File_MARC_Record
    {
        $fields = json_decode($catalog->field_data ?: '[]', true);
        $fields = is_array($fields) ? $fields : [];

        $record = new File_MARC_Record();
        $record->setLeader(self::LEADER_TEMPLATE);

        // MARC requires LDR first, then control fields (00X) ascending, then data fields ascending.
        usort($fields, function ($a, $b) {
            $ca = (string) ($a['marctag']['code'] ?? '');
            $cb = (string) ($b['marctag']['code'] ?? '');
            if ($ca === 'LDR') return $cb === 'LDR' ? 0 : -1;
            if ($cb === 'LDR') return 1;
            return strcmp($ca, $cb);
        });

        foreach ($fields as $field) {
            $code = (string) ($field['marctag']['code'] ?? '');
            $subs = $field['subfield'] ?? [];

            if ($code === 'LDR') {
                $record->setLeader($this->assembleFixed($subs, self::LEADER_TEMPLATE));
                continue;
            }

            if ($this->isControlTag($code)) {
                $data = $this->controlFieldData($subs);
                if ($data !== '') {
                    $record->appendField(new File_MARC_Control_Field($code, $data));
                }
                continue;
            }

            // Data field: two normalised indicators + subfields (rows with a real code).
            $subfields = [];
            foreach ($subs as $s) {
                $sc = $this->subfieldCode($s['code'] ?? '');
                if ($sc === null) continue;
                $subfields[] = new File_MARC_Subfield($sc, (string) ($s['value'] ?? ''));
            }
            if (! $subfields) continue;

            $ind1 = self::indicatorByte($field['indicator'][0]['indicator'] ?? ' ');
            $ind2 = self::indicatorByte($field['indicator'][1]['indicator'] ?? ' ');
            $record->appendField(new File_MARC_Data_Field($code, $subfields, $ind1, $ind2));
        }

        return $record;
    }

    /** MARC21 binary (ISO 2709). Concatenated for a batch. */
    public function toBinary(array $records): string
    {
        return implode('', array_map(fn (File_MARC_Record $r) => $r->toRaw(), $records));
    }

    /** MARCXML slim collection (one <record> per catalog; collection-of-one for a single export). */
    public function toXml(array $records): string
    {
        $w = new XMLWriter();
        $w->openMemory();
        $w->setIndent(true);
        $this->xmlCollectionStart($w);
        foreach ($records as $record) {
            $this->writeRecordXml($w, $record);
        }
        $this->xmlCollectionEnd($w);
        return $w->outputMemory();
    }

    /** Open a MARCXML <collection> (with the XML declaration) on a writer — for streaming a batch. */
    public function xmlCollectionStart(XMLWriter $w): void
    {
        $w->startDocument('1.0', 'UTF-8');
        $w->startElement('collection');
        $w->writeAttribute('xmlns', 'http://www.loc.gov/MARC21/slim');
    }

    /** Close the <collection> + document. */
    public function xmlCollectionEnd(XMLWriter $w): void
    {
        $w->endElement(); // collection
        $w->endDocument();
    }

    /** Write a single <record> element to an open writer (shared by toXml + the batch stream). */
    public function writeRecordXml(XMLWriter $w, File_MARC_Record $record): void
    {
        $w->startElement('record');

        // Leader: same schema-safe defaults File_MARC applies (status n / type a when blank).
        $leader = $record->getLeader();
        if (($leader[5] ?? '') === ' ') $leader[5] = 'n';
        if (($leader[6] ?? '') === ' ') $leader[6] = 'a';
        $w->writeElement('leader', $leader);

        foreach ($record->getFields() as $field) {
            if ($field->isEmpty()) continue;

            if ($field instanceof File_MARC_Control_Field) {
                $w->startElement('controlfield');
                $w->writeAttribute('tag', $field->getTag());
                $w->text($field->getData());
                $w->endElement();
            } elseif ($field instanceof File_MARC_Data_Field) {
                $w->startElement('datafield');
                $w->writeAttribute('tag', $field->getTag());
                $w->writeAttribute('ind1', $field->getIndicator(1));
                $w->writeAttribute('ind2', $field->getIndicator(2));
                foreach ($field->getSubfields() as $sf) {
                    $w->startElement('subfield');
                    $w->writeAttribute('code', $sf->getCode());
                    $w->text($sf->getData());
                    $w->endElement();
                }
                $w->endElement();
            }
        }

        $w->endElement(); // record
    }

    // ---- helpers ----------------------------------------------------------

    /** A MARC indicator is one character: a digit/lowercase letter, or a space for blank. */
    public static function indicatorByte($val): string
    {
        $t = trim((string) $val);
        if ($t === '') return ' ';
        if (preg_match('/[\s-]/', $t)) {                 // "<char> - <description>"
            $t = preg_split('/[\s-]+/', $t)[0];
        }
        if ($t === '' || $t === '#' || $t === '\\') return ' ';   // blank conventions
        if (strlen($t) === 1 && preg_match('/[0-9a-z]/', $t)) return $t;
        return ' ';                                       // legacy 'N'/'None' or anything else
    }

    private function isControlTag(string $code): bool
    {
        return (bool) preg_match('/^00[0-9]$/', $code);
    }

    /**
     * A data-field subfield code from a field_data code like "245$a" -> "a". Returns null for
     * placeholder/unpicked rows and the "00X-NS" control-field markers.
     */
    private function subfieldCode($code): ?string
    {
        $code = (string) $code;
        if (! str_contains($code, '$')) return null;
        $c = substr($code, strpos($code, '$') + 1, 1);
        return $c === '' ? null : $c;
    }

    /**
     * Control-field data. If the subfields are byte-position style (006/007/008: "008$07-10")
     * assemble the fixed-length string; otherwise (001/003/005) use the single stored value.
     */
    private function controlFieldData(array $subs): string
    {
        $positional = false;
        foreach ($subs as $s) {
            if (preg_match('/\$\d+(-\d+)?$/', (string) ($s['code'] ?? ''))) { $positional = true; break; }
        }
        if ($positional) {
            return rtrim($this->assembleFixed($subs, ''), " \0");
        }
        foreach ($subs as $s) {
            $v = (string) ($s['value'] ?? '');
            if ($v !== '') return $v;
        }
        return '';
    }

    /**
     * Overlay byte-position subfields onto a fixed-length string. Codes look like "LDR$05",
     * "LDR$00-04", "008$07-10", "008$18-34" — the range after '$' is the byte span. Positions
     * not supplied keep the template char (leader) or a space (control fields).
     */
    private function assembleFixed(array $subs, string $template): string
    {
        $chars = str_split($template);
        foreach ($subs as $s) {
            $range = $this->positionRange((string) ($s['code'] ?? ''));
            if ($range === null) continue;
            [$start, $end] = $range;
            $val = (string) ($s['value'] ?? '');
            for ($i = 0; $i <= $end - $start; $i++) {
                $pos = $start + $i;
                // grow the buffer for control fields (empty template) up to this position
                while (count($chars) <= $pos) $chars[] = ' ';
                $chars[$pos] = $val[$i] ?? ' ';
            }
        }
        return implode('', $chars);
    }

    /** "LDR$05" -> [5,5]; "008$07-10" -> [7,10]; non-positional code -> null. */
    private function positionRange(string $code): ?array
    {
        if (! preg_match('/\$(\d+)(?:-(\d+))?$/', $code, $m)) return null;
        $start = (int) $m[1];
        $end = isset($m[2]) ? (int) $m[2] : $start;
        return $end >= $start ? [$start, $end] : null;
    }
}
