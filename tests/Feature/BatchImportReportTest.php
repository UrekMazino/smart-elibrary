<?php

namespace Tests\Feature;

use App\Models\CatalogImportIssue;
use App\Models\User;
use File_MARC;
use File_MARC_Control_Field;
use File_MARC_Data_Field;
use File_MARC_Record;
use File_MARC_Subfield;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\SeedsReferenceData;
use Tests\TestCase;

/**
 * Batch import: every record is imported, imported and flagged for review, or skipped — and the
 * import report says which and why (App\Services\CatalogRecordCheck).
 */
class BatchImportReportTest extends TestCase
{
    use RefreshDatabase, SeedsReferenceData;

    private User $staff;
    private int $material;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staff = User::factory()->create(['usertype' => 'Developer']);
        $this->staff->assignRole(Role::findOrCreate('developer'));
        $this->material = DB::table('materials')->insertGetId(['name' => 'Book', 'is_active' => 1]);
        // No generated cover images in public/ from a test import.
        DB::table('settings')->updateOrInsert(['key' => 'enable_placeholder_covers'], ['value' => '0']);
        // The importer looks every tag, subfield and indicator up in the MARC 21 tables.
        $this->seedReferenceData('marctags_loc', 'marctag_indicators_loc', 'marctag_subfields_loc');
    }

    /** One MARC21 record: [tag => control-field data | [subfield code => data]]. */
    private function marc(array $fields): string
    {
        $record = new File_MARC_Record();
        $record->setLeader('00000nam a2200000 a 4500');
        foreach ($fields as $tag => $value) {
            if (is_string($value)) {
                $record->appendField(new File_MARC_Control_Field((string) $tag, $value));
                continue;
            }
            $subfields = [];
            foreach ($value as $code => $data) $subfields[] = new File_MARC_Subfield($code, $data);
            $record->appendField(new File_MARC_Data_Field((string) $tag, $subfields, ' ', ' '));
        }
        return $record->toRaw();
    }

    private function import(): int
    {
        $file = implode('', [
            $this->marc(['001' => 'C1', '020' => ['a' => '9789710000001'], '100' => ['a' => 'Cruz, Juan.'], '245' => ['a' => 'Complete record'], '260' => ['b' => 'PCAARRD,', 'c' => '2001.']]),
            $this->marc(['001' => 'C2', '008' => '120101s2012', '245' => ['a' => 'No author or publisher'], '260' => ['c' => '1999']]),
            $this->marc(['001' => 'C3', '245' => ['a' => 'Only a title']]),
            $this->marc(['001' => 'C4', '100' => ['a' => 'Santos, Ana.'], '260' => ['c' => '2010']]),
            $this->marc(['001' => 'C5', '020' => ['a' => '9789710000001'], '100' => ['a' => 'Reyes, Jose.'], '245' => ['a' => 'Same ISBN'], '260' => ['b' => 'UPLB,', 'c' => '2005']]),
        ]);

        $this->actingAs($this->staff)->post('/admin/create-batch/store', [
            'batch_file' => UploadedFile::fake()->createWithContent('records.mrc', $file),
            'subject' => 'Test batch',
            'material_type_id' => $this->material,
        ])->assertRedirect('/admin/batch-list')->assertSessionHasNoErrors();

        return (int) DB::table('catalog_imports')->value('id');
    }

    public function test_records_are_imported_flagged_or_skipped_and_reported()
    {
        $batch = $this->import();

        $this->assertSame(['Complete record', 'No author or publisher', 'Same ISBN'],
            DB::table('view_catalogs')->orderBy('catalog_id')->pluck('title_statement')->all());
        $import = DB::table('catalog_imports')->find($batch);
        $this->assertSame([5, 3, 2, 'records.mrc'], [(int) $import->no_of_records, (int) $import->imported_count, (int) $import->skipped_count, $import->file_name]);
        $this->assertSame($batch, session('import_report'));
        $this->assertStringContainsString('Imported 3 of 5 record(s)', session('success'));

        $issues = CatalogImportIssue::orderBy('record_no')->get()->keyBy('control_number');
        $codes = fn ($c) => collect($issues[$c]->problems)->pluck('code')->all();
        $this->assertSame(['short_008', 'no_author', 'no_publisher'], $codes('C2'));
        $this->assertSame(['too_little_data'], $codes('C3'));
        $this->assertSame(['no_title'], $codes('C4'));
        $this->assertSame(['possible_duplicate'], $codes('C5'));
        $this->assertSame(['imported', 'skipped', 'skipped', 'imported'], $issues->pluck('outcome')->values()->all());
        $this->assertSame([2, 3, 4, 5], $issues->pluck('record_no')->values()->all());
    }

    public function test_an_unreadable_record_is_skipped_and_the_rest_of_the_file_still_imports()
    {
        // Without the "tag not found" entry, a tag missing from the MARC 21 table can't be read.
        DB::table('marctag_subfields_loc')->where('marctags_loc_id', 2500)->delete();
        DB::table('marctags_loc')->where('id', 2500)->delete();
        $file = $this->marc(['001' => 'U1', '100' => ['a' => 'Cruz, Juan.'], '245' => ['a' => 'Has a local tag'], '977' => ['a' => 'x']])
              . $this->marc(['001' => 'U2', '100' => ['a' => 'Cruz, Juan.'], '245' => ['a' => 'Fine record'], '260' => ['c' => '2001']]);

        $this->actingAs($this->staff)->post('/admin/create-batch/store', [
            'batch_file' => UploadedFile::fake()->createWithContent('records.mrc', $file),
            'subject' => 'Test batch', 'material_type_id' => $this->material,
        ])->assertRedirect('/admin/batch-list');

        $this->assertSame(['Fine record'], DB::table('view_catalogs')->pluck('title_statement')->all());
        $issue = CatalogImportIssue::where('control_number', 'U1')->first();
        $this->assertSame(['U1', 'skipped', 'Has a local tag', 'unreadable'],
            [$issue->control_number, $issue->outcome, $issue->title, $issue->problems[0]['code']]);
    }

    public function test_the_report_lists_problems_and_returns_the_skipped_records_as_marc()
    {
        $batch = $this->import();

        $this->actingAs($this->staff)->get("/admin/batch-report/$batch")->assertOk()
            ->assertSee('Only a title')->assertSee('No title (245 $a).')->assertSee('No author or publisher')
            ->assertSee('Possible duplicate')->assertSee('Download skipped records');

        $download = $this->actingAs($this->staff)->get("/admin/batch-report/$batch/skipped.mrc")->assertOk();
        $marc = new File_MARC($download->streamedContent(), File_MARC::SOURCE_STRING);
        $numbers = [];
        while ($record = $marc->next()) $numbers[] = $record->getField('001')->getData();
        $this->assertSame(['C3', 'C4'], $numbers);

        // The batch list shows what needs attention.
        $row = $this->actingAs($this->staff)->getJson('/admin/batch-list-json?draw=1&start=0&length=10')->json('aaData.0');
        $this->assertSame([2, 2], [$row['skipped'], $row['review_open']]);
    }

    public function test_a_flagged_record_can_be_marked_reviewed()
    {
        $batch = $this->import();
        $issue = CatalogImportIssue::where('control_number', 'C5')->first();

        $this->actingAs($this->staff)->post("/admin/import-issues/{$issue->id}/reviewed")
            ->assertRedirect("/admin/batch-report/$batch");

        $this->assertNotNull($issue->fresh()->reviewed_at);
        $this->assertSame($this->staff->id, (int) $issue->fresh()->reviewed_by);
    }

    public function test_editing_a_flagged_record_clears_what_was_fixed()
    {
        $this->import();
        $issue = CatalogImportIssue::where('control_number', 'C2')->first();
        $fields = json_decode(DB::table('catalogs')->where('id', $issue->catalog_id)->value('field_data'), true);

        // Add the publisher; the author is still missing.
        foreach ($fields as &$field) {
            if ($field['marctag']['code'] === '260') {
                $field['subfield'][] = ['sub-index' => 2, 'id' => 0, 'code' => '260$b', 'description' => 'Publisher', 'value' => 'PCAARRD'];
            }
        }
        unset($field);

        $this->actingAs($this->staff)->post("/admin/edit-record/update/{$issue->catalog_id}", [
            'field_data' => json_encode($fields), 'material_type' => $this->material,
        ])->assertRedirect();

        $issue->refresh();
        $this->assertNull($issue->reviewed_at);
        $this->assertSame(['no_author'], collect($issue->problems)->pluck('code')->all());
    }
}
