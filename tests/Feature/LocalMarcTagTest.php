<?php

namespace Tests\Feature;

use App\Models\User;
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
 * Local tags have no standard name: the tag number is the tag's name and the subfield code the
 * subfield's. 900–999 (Koha's 942 item type, 952 holdings, 999 ids) have rows of their own, so Add
 * Tag offers them; a tag the tables don't have at all (090) gets the "not found" placeholder.
 * Imported, opened in the editor or edited, a record keeps its own tags, every one of them.
 */
class LocalMarcTagTest extends TestCase
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
        $this->seedReferenceData('marctags_loc', 'marctag_indicators_loc', 'marctag_subfields_loc');
    }

    /** A Koha-style record: MARC 21 fields, the local 942, 952 and 999, and 090, a tag the tables don't have. */
    private function marc(): string
    {
        $record = new File_MARC_Record();
        $record->setLeader('00000nam a2200000 a 4500');
        $record->appendField(new File_MARC_Control_Field('001', 'K1'));
        $fields = [
            '090' => ['a' => 'QA76.9'],
            '100' => ['a' => 'Cruz, Juan.'],
            '245' => ['a' => 'A Koha record'],
            '260' => ['b' => 'FNRI,', 'c' => '2001.'],
            '942' => ['2' => 'lcc', 'c' => 'FN'],
            '952' => ['a' => 'MAIN', 'p' => 'B000123'],
            '999' => ['c' => '17'],
        ];
        foreach ($fields as $tag => $subs) {
            $list = [];
            foreach ($subs as $code => $data) $list[] = new File_MARC_Subfield((string) $code, $data);
            $record->appendField(new File_MARC_Data_Field($tag, $list, ' ', ' '));
        }
        return $record->toRaw();
    }

    private function batchImport(): int
    {
        $this->actingAs($this->staff)->post('/admin/create-batch/store', [
            'batch_file' => UploadedFile::fake()->createWithContent('koha.mrc', $this->marc()),
            'subject' => 'Koha batch',
            'material_type_id' => $this->material,
        ])->assertRedirect('/admin/batch-list')->assertSessionHasNoErrors();

        return (int) DB::table('catalogs')->value('id');
    }

    /** The row ids of a tag and its subfields: [tag id, [subfield code => id]]. */
    private function ids(string $tag): array
    {
        $id = DB::table('marctags_loc')->where('code', $tag)->where('id', '<>', MARCTAG_NOT_FOUND_ID)->value('id');
        return [$id, DB::table('marctag_subfields_loc')->where('marctags_loc_id', $id)->pluck('id', 'code')->all()];
    }

    /** tag => [name, [subfield code => [id, name]]] */
    private function summary(array $fields, string $tag = 'marctag', string $subs = 'subfield'): array
    {
        $out = [];
        foreach ($fields as $f) {
            $out[$f[$tag]['code']] = [$f[$tag]['description'],
                collect($f[$subs])->mapWithKeys(fn ($s) => [$s['code'] => [$s['id'], $s['description']]])->all()];
        }
        return $out;
    }

    public function test_batch_import_names_a_local_tag_by_its_number_and_keeps_every_one()
    {
        $stored = json_decode(DB::table('catalogs')->where('id', $this->batchImport())->value('field_data'), true);
        $fields = $this->summary($stored);
        [, $sub942] = $this->ids('942');
        [, $sub952] = $this->ids('952');

        $this->assertSame(['942', ['942$2' => [$sub942['942$2'], '942$2'], '942$c' => [$sub942['942$c'], '942$c']]], $fields['942']);
        $this->assertSame(['952', ['952$a' => [$sub952['952$a'], '952$a'], '952$p' => [$sub952['952$p'], '952$p']]], $fields['952']);
        $this->assertSame('999', $fields['999'][0]);   // not the placeholder row's "Marctag not found"
        $this->assertSame($this->ids('999')[0], (int) collect($stored)->firstWhere('marctag.code', '999')['marctag']['id']);
        $this->assertSame(['090', ['090$a' => [null, '090$a']]], $fields['090']);   // no row: the placeholder, named by number
        $this->assertSame(MARCTAG_NOT_FOUND_ID, (int) collect($stored)->firstWhere('marctag.code', '090')['marctag']['id']);
        $this->assertSame('Title Statement', $fields['245'][0]);
        $this->assertSame('Title', $fields['245'][1]['245$a'][1]);
    }

    public function test_opening_a_marc_file_in_the_editor_keeps_local_tags()
    {
        $res = $this->actingAs($this->staff)->post('/admin/import/single-marcfile', [
            'marcFile' => UploadedFile::fake()->createWithContent('koha.mrc', $this->marc()),
        ])->assertOk();

        $fields = $this->summary(json_decode($res->viewData('fieldData'), true)['fields'], 'tag', 'subfields');
        [, $sub942] = $this->ids('942');
        $this->assertSame(['LDR', '001', '090', '100', '245', '260', '942', '952', '999'], array_map('strval', array_keys($fields)));
        $this->assertSame('Leader', $fields['LDR'][0]);
        $this->assertSame(['942', ['942$2' => [$sub942['942$2'], '942$2'], '942$c' => [$sub942['942$c'], '942$c']]], $fields['942']);
        $this->assertSame(['090', ['090$a' => [null, '090$a']]], $fields['090']);
        $this->assertSame('Title', $fields['245'][1]['245$a'][1]);
    }

    public function test_the_edit_page_offers_each_tags_subfields()
    {
        $id = $this->batchImport();
        $fields = collect($this->actingAs($this->staff)->get("/admin/edit-record/$id")->assertOk()->viewData('recordMarcFields'))
            ->keyBy(fn ($f) => (string) $f['tag']['code']);

        // 942: its own row, $0–$9 and $a–$z.
        $this->assertSame('942', $fields['942']['tag']['description']);
        $this->assertCount(36, $fields['942']['allSubfields']);
        $this->assertContains('942$a', array_column($fields['942']['allSubfields'], 'code'));
        // 090 has no row: it offers the subfield codes it already has.
        $this->assertSame([['id' => null, 'code' => '090$a', 'description' => '090$a']], $fields['090']['allSubfields']);
        $this->assertNotEmpty($fields['245']['allSubfields']);
    }

    public function test_saving_an_edited_record_keeps_its_local_tags()
    {
        $id = $this->batchImport();
        $fields = json_decode(DB::table('catalogs')->where('id', $id)->value('field_data'), true);
        foreach ($fields as &$field) {
            if ($field['marctag']['code'] === '942') $field['subfield'][1]['value'] = 'GOV';   // edit 942$c
        }
        unset($field);

        $this->actingAs($this->staff)->post("/admin/edit-record/update/$id", [
            'field_data' => json_encode($fields), 'material_type' => $this->material,
        ])->assertRedirect();

        $saved = collect(json_decode(DB::table('catalogs')->where('id', $id)->value('field_data'), true))->keyBy(fn ($f) => (string) $f['marctag']['code']);
        $this->assertSame('942', $saved['942']['marctag']['description']);
        $this->assertSame(['lcc', 'GOV'], array_column($saved['942']['subfield'], 'value'));
        $this->assertTrue($saved->has('952') && $saved->has('999'));
    }

    public function test_records_imported_before_are_repaired()
    {
        $old = [
            ['marctag' => ['index' => 1, 'id' => 507, 'code' => '245', 'description' => 'Title Statement'],
             'subfield' => [['sub-index' => 1, 'id' => 507, 'code' => '245$a', 'description' => 'Title', 'value' => 'Old import']], 'indicator' => []],
            ['marctag' => ['index' => 2, 'id' => 2500, 'code' => '942', 'description' => 2500],
             'subfield' => [['sub-index' => 1, 'id' => 'MARCTAG (942$c) not found!', 'code' => '942$c', 'description' => 'MARCTAG (942$c) not found!', 'value' => 'FN']], 'indicator' => []],
        ];
        $id = DB::table('catalogs')->insertGetId(['field_data' => json_encode($old), 'material_type_id' => $this->material, 'created_by' => $this->staff->id]);

        (include database_path('migrations/2026_10_22_100000_local_marc_tags_named_by_number.php'))->up();
        (include database_path('migrations/2026_10_22_200000_local_marc_tags_900_to_999.php'))->up();

        $stored = json_decode(DB::table('catalogs')->where('id', $id)->value('field_data'), true);
        [$tag942, $sub942] = $this->ids('942');
        $this->assertSame(['942', ['942$c' => [$sub942['942$c'], '942$c']]], $this->summary($stored)['942']);
        $this->assertSame($tag942, $stored[1]['marctag']['id']);   // its own row, no longer the placeholder
        $this->assertSame(['Title Statement', ['245$a' => [507, 'Title']]], $this->summary($stored)['245']);
    }

    public function test_add_tag_offers_900_to_999_but_not_the_placeholder()
    {
        $list = fn ($search) => $this->actingAs($this->staff)->getJson("/admin/marc-tags-list/json?length=-1&search[value]=$search")->assertOk()->json('aaData');

        $this->assertContains('942', array_column($list('942'), 'code'));
        $this->assertSame(100, count(array_filter(array_column($list('9'), 'code'), fn ($c) => preg_match('/^9\d\d$/', $c))));   // 900–999
        $this->assertNotContains(MARCTAG_NOT_FOUND_ID, array_column($list('999'), 'id'));
    }
}
