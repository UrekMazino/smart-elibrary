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
 * Tags outside MARC 21 (local ones, such as Koha's 942 item type, 952 holdings, 999 ids) have no
 * name in marctags_loc: the tag number is the tag's name and the subfield code the subfield's.
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

    /** A Koha-style record: MARC 21 fields plus the local 942, 952 and 999. */
    private function marc(): string
    {
        $record = new File_MARC_Record();
        $record->setLeader('00000nam a2200000 a 4500');
        $record->appendField(new File_MARC_Control_Field('001', 'K1'));
        $fields = [
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
        $fields = $this->summary(json_decode(DB::table('catalogs')->where('id', $this->batchImport())->value('field_data'), true));

        $this->assertSame(['942', ['942$2' => [null, '942$2'], '942$c' => [null, '942$c']]], $fields['942']);
        $this->assertSame(['952', ['952$a' => [null, '952$a'], '952$p' => [null, '952$p']]], $fields['952']);
        $this->assertSame('999', $fields['999'][0]);   // not the placeholder row's "Marctag not found"
        $this->assertSame('Title Statement', $fields['245'][0]);
        $this->assertSame('Title', $fields['245'][1]['245$a'][1]);
    }

    public function test_opening_a_marc_file_in_the_editor_keeps_local_tags()
    {
        $res = $this->actingAs($this->staff)->post('/admin/import/single-marcfile', [
            'marcFile' => UploadedFile::fake()->createWithContent('koha.mrc', $this->marc()),
        ])->assertOk();

        $fields = $this->summary(json_decode($res->viewData('fieldData'), true)['fields'], 'tag', 'subfields');
        $this->assertSame(['LDR', '001', '100', '245', '260', '942', '952', '999'], array_map('strval', array_keys($fields)));
        $this->assertSame('Leader', $fields['LDR'][0]);
        $this->assertSame(['942', ['942$2' => [null, '942$2'], '942$c' => [null, '942$c']]], $fields['942']);
        $this->assertSame('Title', $fields['245'][1]['245$a'][1]);
    }

    public function test_the_edit_page_offers_a_local_tags_own_subfields()
    {
        $id = $this->batchImport();
        $fields = collect($this->actingAs($this->staff)->get("/admin/edit-record/$id")->assertOk()->viewData('recordMarcFields'))
            ->keyBy(fn ($f) => $f['tag']['code']);

        $this->assertSame('942', $fields['942']['tag']['description']);
        $this->assertSame([['id' => null, 'code' => '942$2', 'description' => '942$2'], ['id' => null, 'code' => '942$c', 'description' => '942$c']],
            $fields['942']['allSubfields']);
        $this->assertNotEmpty($fields['245']['allSubfields']);   // MARC 21 tags still list every subfield they allow
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

        $fields = $this->summary(json_decode(DB::table('catalogs')->where('id', $id)->value('field_data'), true));
        $this->assertSame(['942', ['942$c' => [null, '942$c']]], $fields['942']);
        $this->assertSame(['Title Statement', ['245$a' => [507, 'Title']]], $fields['245']);
    }

    public function test_the_add_tag_list_does_not_offer_the_not_found_placeholder()
    {
        $rows = $this->actingAs($this->staff)->getJson('/admin/marc-tags-list/json?length=-1&search[value]=999')->assertOk()->json('aaData');
        $this->assertNotContains(MARCTAG_NOT_FOUND_ID, array_column($rows, 'id'));
    }
}
