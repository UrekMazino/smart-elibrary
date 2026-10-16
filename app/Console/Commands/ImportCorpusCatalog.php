<?php

namespace App\Console\Commands;

use App\Models\Catalog;
use App\Models\Frontpage;
use App\Models\Material;
use App\Models\Multimedia;
use App\Models\ViewCatalog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Stage B — turn the Project Y corpus into catalog records.
 *
 * Reads the JSONL produced by rag/catalog_extract.py (Stage A), creates a catalog record
 * per document, copies the PDF in as its full text, and publishes it — unless Stage A
 * flagged it, in which case it lands ON HOLD for a human to sort out.
 *
 *   restricted  the document isn't for open circulation
 *   no-title    no title could be read
 *   junk        not research output (forms, receipts, memos)
 *
 * accession_number holds the corpus `stem`. That's the join key back to the RAG index
 * (whose chunks are keyed by stem), so AI mode can map a retrieved passage to a catalog
 * record without re-embedding anything.
 *
 * Resumable: rerun and it skips stems already imported.
 */
class ImportCorpusCatalog extends Command
{
    protected $signature = 'catalog:import-corpus
                            {--file= : metadata.jsonl from catalog_extract.py}
                            {--limit=0 : only import N records}
                            {--dry-run : report what would happen, write nothing}
                            {--no-covers : skip placeholder cover generation (much faster)}
                            {--publish-all : ignore triage and publish everything (NOT recommended)}';

    protected $description = 'Import the Project Y corpus (metadata.jsonl) as catalog records';

    /**
     * Real ids from marctags_loc / marctag_subfields_loc. The tags mirror what FPRDI's
     * own .mrc records carry (041 language, 100 authors, 245 title, 260 imprint,
     * 300 extent, 520 abstract, 650 subjects).
     */
    private const TAG_LDR = 2501;
    private const TAG_041 = 167;
    private const TAG_245 = 507;
    private const TAG_100 = 349;
    private const TAG_260 = 583;
    private const TAG_300 = 623;
    private const TAG_500 = 1053;
    private const TAG_520 = 1157;
    private const TAG_650 = 1596;

    private const SUB_LDR_06 = 2619;
    private const SUB_041_A  = 167;
    private const SUB_245_A  = 507;
    private const SUB_245_C  = 509;
    private const SUB_100_A  = 349;
    private const SUB_260_B  = 584;
    private const SUB_260_C  = 585;
    private const SUB_300_A  = 623;
    private const SUB_500_A  = 1053;
    private const SUB_520_A  = 1157;
    private const SUB_650_A  = 1596;

    public function handle(): int
    {
        $file = $this->option('file')
            ?: 'C:/Users/jcvia/PyCharmMiscProject/ProjectY/rag/catalog_out/metadata.jsonl';

        if (! is_file($file)) {
            $this->error("Not found: {$file}");
            $this->line('Run rag/catalog_extract.py first (Stage A).');

            return self::FAILURE;
        }

        $dry = (bool) $this->option('dry-run');
        $limit = (int) $this->option('limit');

        $materials = Material::pluck('id', 'name');          // "Books" => 1, ...
        $existing  = Catalog::whereNotNull('accession_number')->pluck('accession_number')->flip();

        $this->info(($dry ? 'DRY RUN — ' : '') . "reading {$file}");
        $this->line('already imported: ' . $existing->count());

        $pdfDir = public_path('pdf');
        if (! $dry) {
            ensurePublicDir($pdfDir);   // public/pdf is a link into storage; its target may not exist yet
        }

        $stats = ['imported' => 0, 'held' => 0, 'published' => 0, 'skipped' => 0,
                  'no_pdf' => 0, 'failed' => 0, 'flags' => []];

        $fh = fopen($file, 'r');
        $n = 0;

        while (($line = fgets($fh)) !== false) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $rec = json_decode($line, true);
            if (! $rec || empty($rec['stem'])) {
                $stats['failed']++;
                continue;
            }

            if ($existing->has($rec['stem'])) {
                $stats['skipped']++;
                continue;
            }

            if ($limit && $n >= $limit) {
                break;
            }
            $n++;

            // Mark this stem as seen NOW, before importing — so a second line with the
            // same stem later in the SAME run is skipped. Without this, $existing only
            // reflects the pre-run DB, and duplicate lines in the JSONL each get imported.
            $existing->put($rec['stem'], true);

            try {
                $this->importOne($rec, $materials, $stats, $dry);
            } catch (\Throwable $e) {
                $stats['failed']++;
                $this->warn("  ! {$rec['stem']}: {$e->getMessage()}");
            }

            if ($n % 100 === 0) {
                $this->line("  {$n} processed…");
            }
        }

        fclose($fh);

        $this->newLine();
        $this->info(($dry ? 'Would import' : 'Imported') . ": {$stats['imported']}");
        $this->line("  published : {$stats['published']}");
        $this->line("  on hold   : {$stats['held']}");
        foreach ($stats['flags'] as $flag => $c) {
            $this->line("      {$flag}: {$c}");
        }
        $this->line("  no PDF    : {$stats['no_pdf']}");
        $this->line("  skipped   : {$stats['skipped']} (already imported)");
        $this->line("  failed    : {$stats['failed']}");

        if (! $dry && $stats['imported'] > 0) {
            clearHomeCache();
            $this->newLine();
            $this->comment('Next: php artisan scout:import "App\Models\ViewCatalog"  (make them searchable)');
        }

        return self::SUCCESS;
    }

    private function importOne(array $rec, $materials, array &$stats, bool $dry): void
    {
        $flags = $rec['flags'] ?? [];
        $hold  = $flags && ! $this->option('publish-all');

        // ?? not ?: — a record whose extraction failed has no 'title'/'year' key at all
        // (only stem/pdf/flags/error), and ?: on a missing key throws "Undefined array key".
        $title = ($rec['title'] ?? null) ?: '[Untitled] ' . $rec['stem'];
        $year  = ($rec['year'] ?? null) ?: null;
        $type  = $rec['type'] ?? 'Books';
        $typeId = $materials[$type] ?? $materials['Books'] ?? 1;

        // A note the librarian sees on the record, explaining why it's on hold.
        $note = trim(($rec['reason'] ?? '') . ($flags ? ' [' . implode(', ', $flags) . ']' : ''));

        foreach ($flags as $f) {
            $stats['flags'][$f] = ($stats['flags'][$f] ?? 0) + 1;
        }

        if ($dry) {
            $stats['imported']++;
            $hold ? $stats['held']++ : $stats['published']++;
            if (empty($rec['pdf']) || ! is_file($rec['pdf'])) {
                $stats['no_pdf']++;
            }

            return;
        }

        DB::transaction(function () use ($rec, $title, $year, $type, $typeId, $hold, $note, &$stats) {
            $catalog = Catalog::create([
                'material_type_id'  => $typeId,
                'is_aanr'           => 0,
                'marc_class_sys_id' => 1,
                // The join key back to the RAG index (chunks are keyed by stem).
                'accession_number'  => $rec['stem'],
                'field_data'        => $this->fieldData($rec, $title, $note),
                'is_published'      => $hold ? 0 : 1,
                'created_by'        => null,   // no account: a console import
                'updated_by'        => null,
            ]);

            $view = ViewCatalog::create([
                'catalog_id'       => $catalog->id,
                'accession_number' => $rec['stem'],
                'material_type'    => $type,
                'material_type_id' => $typeId,
                'title_statement'  => $title,
                // The OPAC reads these columns directly, and Meilisearch indexes them —
                // so the abstract is what makes these records findable by topic rather
                // than by title alone.
                'summary'          => $rec['abstract'] ?? null,
                'publisher'        => $rec['publisher'] ?? null,
                'general_note'     => $note ?: null,
                'pub_year'         => $year,
                'joint_authors'    => $rec['authors'] ?? null,
                'subjects'         => marcSubjects($catalog->field_data),
                'created_by'       => null,
                'updated_by'       => null,
            ]);

            // is_published is NOT in ViewCatalog's $fillable — passing it to create() above
            // would be silently dropped and every record would default to unpublished
            // (catalogs.is_published would then disagree with view_catalogs.is_published,
            // which is the table the OPAC actually reads). Set it explicitly instead of
            // widening $fillable: this field controls public visibility, so it shouldn't
            // become mass-assignable app-wide.
            $view->is_published = $hold ? 0 : 1;
            $view->save();

            // Full text: copy the PDF in the way the app expects (public/pdf + multimedia).
            if (! empty($rec['pdf']) && is_file($rec['pdf'])) {
                $newName = 'ft-' . uniqid() . '.pdf';
                if (@copy($rec['pdf'], public_path('pdf/' . $newName))) {
                    Multimedia::create([
                        'catalog_id'   => $catalog->id,
                        'FileLocation' => 'pdf/' . $newName,
                        'FileName'     => $newName,
                        'FileType'     => 'pdf',
                        'Extension'    => '.pdf',
                    ]);
                }
            } else {
                $stats['no_pdf']++;
            }

            if (! $this->option('no-covers')) {
                ensurePlaceholderCover($catalog->id);
            }

            $stats['imported']++;
            $hold ? $stats['held']++ : $stats['published']++;
        });
    }

    /**
     * MARC field_data in the shape the record editor reads/writes (see
     * CatalogingController::storeRecord, which parses these tag/subfield codes).
     *
     * Tags follow FPRDI's existing .mrc records: 041 language, 100 authors, 245 title +
     * statement of responsibility, 260 imprint, 300 extent, 500 note, 520 abstract,
     * 650 subjects. Only fields the document actually stated are written — an absent
     * tag is honest, an empty one is noise.
     */
    private function fieldData(array $rec, string $title, ?string $note): string
    {
        $fields = [];
        $i = 0;

        $add = function (int $tagId, string $code, string $desc, array $subs) use (&$fields, &$i) {
            $subfields = [];
            $n = 0;
            foreach ($subs as [$subId, $subCode, $subDesc, $value]) {
                if ($value === null || $value === '') {
                    continue;
                }
                $subfields[] = ['sub-index' => ++$n, 'id' => $subId, 'code' => $subCode,
                                'description' => $subDesc, 'value' => $value];
            }
            if ($subfields) {
                $fields[] = [
                    'marctag'  => ['index' => ++$i, 'id' => (string) $tagId, 'code' => $code, 'description' => $desc],
                    // Two blank indicators ("N" = undefined), matching how the record editor
                    // builds field_data (CatalogingController). The view/edit record JS maps
                    // over `indicator`, so it must be present, not omitted.
                    'indicator' => [['indicator' => 'N'], ['indicator' => 'N']],
                    'subfield'  => $subfields,
                ];
            }
        };

        $add(self::TAG_LDR, 'LDR', 'Leader', [
            [self::SUB_LDR_06, 'LDR$06', 'Type of record', 'a'],
        ]);

        $add(self::TAG_041, '041', 'Language Code', [
            [self::SUB_041_A, '041$a', 'Language code of text/sound track or separate title', $rec['language'] ?? null],
        ]);

        $add(self::TAG_100, '100', 'Main Entry - Personal Name', [
            [self::SUB_100_A, '100$a', 'Personal name', $rec['authors'] ?? null],
        ]);

        $add(self::TAG_245, '245', 'Title Statement', [
            [self::SUB_245_A, '245$a', 'Title', $title],
            [self::SUB_245_C, '245$c', 'Statement of responsibility, etc.', $rec['responsibility'] ?? null],
        ]);

        $add(self::TAG_260, '260', 'Publication, Distribution, etc. (Imprint)', [
            [self::SUB_260_B, '260$b', 'Name of publisher, distributor, etc.', $rec['publisher'] ?? null],
            [self::SUB_260_C, '260$c', 'Date of publication, distribution, etc.', $rec['year'] ?? null],
        ]);

        // Page count comes from the index (a fact), not from the model.
        $add(self::TAG_300, '300', 'Physical Description', [
            [self::SUB_300_A, '300$a', 'Extent', !empty($rec['pages']) ? $rec['pages'] . ' p.' : null],
        ]);

        $add(self::TAG_520, '520', 'Summary, etc.', [
            [self::SUB_520_A, '520$a', 'Summary, etc.', $rec['abstract'] ?? null],
        ]);

        $add(self::TAG_650, '650', 'Subject Added Entry - Topical Term', [
            [self::SUB_650_A, '650$a', 'Topical term or geographic name entry element', $rec['subjects'] ?? null],
        ]);

        $add(self::TAG_500, '500', 'General Note', [
            [self::SUB_500_A, '500$a', 'General note', $note ?: null],
        ]);

        return json_encode($fields, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
