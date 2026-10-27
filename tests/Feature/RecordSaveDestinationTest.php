<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\SeedsReferenceData;
use Tests\TestCase;

/**
 * Create Record brings back an empty Create page for the next record (its notice links to the new one);
 * Update Record keeps the cataloguer in the editor. "Add and View Record" / "Update and View Record"
 * (after=view) open the record's page. Each says it was saved.
 */
class RecordSaveDestinationTest extends TestCase
{
    use RefreshDatabase, SeedsReferenceData;

    private User $staff;
    private int $material;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAccess();
        $this->staff = User::factory()->create(['usertype' => 'Developer']);
        $this->staff->assignRole(Role::findOrCreate('developer'));
        $this->material = DB::table('materials')->insertGetId(['name' => 'Book', 'is_active' => 1]);
    }

    private function fields(string $title): string
    {
        return json_encode([[
            'marctag'   => ['index' => 1, 'id' => 507, 'code' => '245', 'description' => 'Title Statement'],
            'subfield'  => [['sub-index' => 1, 'id' => 507, 'code' => '245$a', 'description' => 'Title', 'value' => $title]],
            'indicator' => [],
        ]]);
    }

    private function create(array $extra = [], ?User $as = null)
    {
        return $this->actingAs($as ?? $this->staff)->post('/admin/create-record/store',
            ['field_data' => $this->fields('Malunggay and iron'), 'material_type' => $this->material] + $extra);
    }

    private function newestId(): int
    {
        return (int) DB::table('catalogs')->max('id');
    }

    public function test_create_record_brings_back_an_empty_create_page_for_the_next_one()
    {
        $res = $this->create()->assertRedirect('/admin/create-record')->assertSessionHas('success', 'Record created.');
        $res->assertSessionHas('success_link', ['url' => url("/admin/view-record/{$this->newestId()}"), 'label' => 'View record']);

        // And a second record right after: a new record, not an update of the first.
        $this->create();
        $this->assertSame(2, DB::table('catalogs')->count());
    }

    public function test_add_and_view_record_opens_the_new_records_page()
    {
        $this->create(['after' => 'view'])->assertRedirect("/admin/view-record/{$this->newestId()}")->assertSessionHas('success', 'Record created.');
    }

    public function test_update_record_stays_and_update_and_view_opens_the_page()
    {
        $this->create();
        $id = $this->newestId();
        $update = fn (array $extra = []) => $this->actingAs($this->staff)->post("/admin/edit-record/update/$id",
            ['field_data' => $this->fields('Malunggay and iron, revised'), 'material_type' => $this->material] + $extra);

        $update()->assertRedirect("/admin/edit-record/$id")->assertSessionHas('success', 'Record saved.');
        $update(['after' => 'view'])->assertRedirect("/admin/view-record/$id")->assertSessionHas('success', 'Record saved.');
    }

    public function test_someone_who_may_create_but_not_edit_can_add_another_or_view()
    {
        $creator = User::factory()->create(['usertype' => 'Administrator']);
        $creator->assignRole(Role::create(['name' => 'creator_only', 'guard_name' => 'web'])->givePermissionTo(['access.cataloging', 'create.catalog']));

        $this->create([], $creator)->assertRedirect('/admin/create-record');
        $this->actingAs($creator)->get('/admin/create-record')->assertOk();
        $this->create(['after' => 'view'], $creator)->assertRedirect("/admin/view-record/{$this->newestId()}");
    }

    public function test_both_pages_offer_both_buttons_and_show_the_notice()
    {
        $this->actingAs($this->staff)->get('/admin/create-record')->assertOk()
            ->assertSee('Create Record')->assertSee('name="after" value="view"', false)->assertSee('Add and View Record');

        $this->create();
        $id = $this->newestId();
        $this->actingAs($this->staff)->get("/admin/edit-record/$id")->assertOk()
            ->assertSee('Update Record')->assertSee('Update and View Record')->assertDontSee("showSwal('custom-position')", false);

        // After Create Record: the empty Create page, its notice linking to the new record.
        $this->actingAs($this->staff)->withSession(['success' => 'Record created.', 'success_link' => ['url' => url("/admin/view-record/$id"), 'label' => 'View record']])
            ->get('/admin/create-record')->assertSee('"Record created."', false)->assertSee('View record')
            ->assertSee(str_replace('/', '\/', url("/admin/view-record/$id")), false);

        // After a save the notice carries the message (the old template call showed a fixed text, and wasn't loaded).
        $this->actingAs($this->staff)->withSession(['success' => 'Record saved.'])->get("/admin/edit-record/$id")
            ->assertSee('"Record saved."', false);
        $this->actingAs($this->staff)->withSession(['success' => 'Record saved.'])->get("/admin/view-record/$id")
            ->assertSee('"Record saved."', false);
    }
}
