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

    /** Real ids from marctags_loc / marctag_subfields_loc — see field_data below. */
    private const TAG_LDR = 2501;
    private const TAG_245 = 507;
    private const TAG_100 = 349;
    private const TAG_260 = 583;
    private const TAG_500 = 1053;

    private const SUB_LDR_06 = 2619;
    private const SUB_245_A  = 507;
    private const SUB_100_A  = 349;
    private const SUB_260_C  = 585;
    private const SUB_500_A  = 1053;

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
        if (! $dry && ! is_dir($pdfDir)) {
            mkdir($pdfDir, 0775, true);
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

        $title = $rec['title'] ?: '[Untitled] ' . $rec['stem'];
        $year  = $rec['year'] ?: null;
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
                'field_data'        => $this->fieldData($title, $rec['authors'] ?? null, $year, $note),
                'is_published'      => $hold ? 0 : 1,
                'created_by'        => 'corpus-import',
                'updated_by'        => 'corpus-import',
            ]);

            $view = ViewCatalog::create([
                'catalog_id'       => $catalog->id,
                'accession_number' => $rec['stem'],
                'material_type'    => $type,
                'material_type_id' => $typeId,
                'title_statement'  => $title,
                'general_note'     => $note ?: null,
                'pub_year'         => $year,
                'joint_authors'    => $rec['authors'] ?? null,
                'created_by'       => 'corpus-import',
                'updated_by'       => 'corpus-import',
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
     * Minimal but valid MARC field_data, in the shape the record editor reads/writes
     * (see CatalogingController::storeRecord, which parses these tag/subfield codes).
     */
    private function fieldData(string $title, ?string $authors, ?string $year, ?string $note): string
    {
        $fields = [];
        $i = 0;

        $fields[] = [
            'marctag' => ['index' => ++$i, 'id' => (string) self::TAG_LDR, 'code' => 'LDR', 'description' => 'Leader'],
            'subfield' => [
                ['sub-index' => 1, 'id' => self::SUB_LDR_06, 'code' => 'LDR$06', 'description' => 'Type of record', 'value' => 'a'],
            ],
        ];

        $fields[] = [
            'marctag' => ['index' => ++$i, 'id' => (string) self::TAG_245, 'code' => '245', 'description' => 'Title Statement'],
            'subfield' => [
                ['sub-index' => 1, 'id' => self::SUB_245_A, 'code' => '245$a', 'description' => 'Title', 'value' => $title],
            ],
        ];

        if ($authors) {
            $fields[] = [
                'marctag' => ['index' => ++$i, 'id' => (string) self::TAG_100, 'code' => '100', 'description' => 'Main Entry - Personal Name'],
                'subfield' => [
                    ['sub-index' => 1, 'id' => self::SUB_100_A, 'code' => '100$a', 'description' => 'Personal name', 'value' => $authors],
                ],
            ];
        }

        if ($year) {
            $fields[] = [
                'marctag' => ['index' => ++$i, 'id' => (string) self::TAG_260, 'code' => '260', 'description' => 'Publication, Distribution, etc. (Imprint)'],
                'subfield' => [
                    ['sub-index' => 1, 'id' => self::SUB_260_C, 'code' => '260$c', 'description' => 'Date of publication, distribution, etc.', 'value' => $year],
                ],
            ];
        }

        if ($note) {
            $fields[] = [
                'marctag' => ['index' => ++$i, 'id' => (string) self::TAG_500, 'code' => '500', 'description' => 'General Note'],
                'subfield' => [
                    ['sub-index' => 1, 'id' => self::SUB_500_A, 'code' => '500$a', 'description' => 'General note', 'value' => $note],
                ],
            ];
        }

        return json_encode($fields, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
