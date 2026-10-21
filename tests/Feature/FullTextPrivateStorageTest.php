<?php

namespace Tests\Feature;

use App\Models\Multimedia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Full texts live in private storage (storage/app/private/pdf), out of the web server's reach: only
 * Read and Download (signed-in, published records) and the staff preview hand them out.
 */
class FullTextPrivateStorageTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;
    private User $patron;
    private int $material;
    private string $tmp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->staff = User::factory()->create(['usertype' => 'Developer']);
        $this->staff->assignRole(Role::findOrCreate('developer'));
        $this->patron = User::factory()->create(['usertype' => 'Patron']);
        $this->material = DB::table('materials')->insertGetId(['name' => 'Book']);

        // Public and storage folders of the test's own: the move command sweeps both.
        $this->tmp = sys_get_temp_dir() . '/fulltext-private-' . uniqid();
        File::ensureDirectoryExists("{$this->tmp}/public/js/admin");
        File::copy(public_path('js/admin/catalog-view-record.js'), "{$this->tmp}/public/js/admin/catalog-view-record.js");
        $this->app->usePublicPath("{$this->tmp}/public");
        $this->app->useStoragePath("{$this->tmp}/storage");
        config(['filesystems.disks.private.root' => "{$this->tmp}/storage/app/private"]);
        Storage::forgetDisk('private');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->tmp);
        parent::tearDown();
    }

    private function record(bool $published = true): int
    {
        $id = DB::table('catalogs')->insertGetId(['material_type_id' => $this->material, 'field_data' => '[]', 'is_published' => (int) $published]);
        DB::table('view_catalogs')->insert(['catalog_id' => $id, 'title_statement' => 'Rubber production',
            'material_type_id' => $this->material, 'is_published' => (int) $published]);
        return $id;
    }

    private function fullText(int $id, string $file = 'ft-a.pdf'): void
    {
        Multimedia::create(['catalog_id' => $id, 'FileName' => 'Annual report.pdf', 'FileLocation' => "pdf/$file", 'FileType' => 'pdf']);
    }

    public function test_an_uploaded_full_text_goes_to_private_storage()
    {
        $id = $this->record();

        $this->actingAs($this->staff)->post('/admin/upload/full-text', [
            'catalog_id' => $id, 'fullText' => UploadedFile::fake()->create('report.pdf', 20, 'application/pdf'),
        ])->assertOk();

        $location = Multimedia::where('catalog_id', $id)->value('FileLocation');
        $this->assertMatchesRegularExpression('#^pdf/ft-[0-9a-f]+\.pdf$#', $location);
        $this->assertFileExists("{$this->tmp}/storage/app/private/$location");
        $this->assertFileDoesNotExist("{$this->tmp}/public/$location");

        // The OPAC reads it from there.
        $this->actingAs($this->patron)->get("/read/$id")->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_staff_open_a_held_records_full_text_and_patrons_cannot()
    {
        $id = $this->record(published: false);
        $this->fullText($id);
        Storage::disk('private')->put('pdf/ft-a.pdf', "%PDF-1.4\n%%EOF\n");

        $this->actingAs($this->staff)->get("/admin/full-text/$id")->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->actingAs($this->staff)->get("/admin/full-text/$id?download=1")->assertDownload('Annual report.pdf');
        $this->actingAs($this->staff)->get("/admin/view-record/$id")->assertOk()->assertSee(route('full-text', $id));

        $this->actingAs($this->patron)->get("/admin/full-text/$id")->assertForbidden();
        $this->actingAs($this->patron)->get("/read/$id")->assertNotFound();   // held: not for patrons
    }

    public function test_an_unknown_record_is_not_found_rather_than_an_error()
    {
        $this->actingAs($this->staff)->get('/admin/view-record/999999')->assertNotFound();
        $this->actingAs($this->staff)->get('/admin/view-record/add-copy')->assertMethodNotAllowed();   // a POST-only address, not a record
        $this->actingAs($this->staff)->get('/admin/full-text/999999')->assertNotFound();
    }

    public function test_the_move_command_takes_the_files_out_of_the_public_folders()
    {
        $id = $this->record();
        $this->fullText($id);
        // The old layout: storage/app/public/pdf, reached through the public/pdf link.
        $old = "{$this->tmp}/storage/app/public/pdf";
        File::ensureDirectoryExists($old);
        file_put_contents("$old/ft-a.pdf", "%PDF-1.4\n%%EOF\n");
        symlink($old, "{$this->tmp}/public/pdf");

        $this->artisan('fulltext:make-private --dry-run')->expectsOutputToContain('Would move 1 full text(s)')->assertSuccessful();
        $this->assertFileExists("$old/ft-a.pdf");

        $this->artisan('fulltext:make-private')->expectsOutputToContain('Moved 1 full text(s)')->assertSuccessful();
        $this->assertFileExists("{$this->tmp}/storage/app/private/pdf/ft-a.pdf");
        $this->assertFalse(is_link("{$this->tmp}/public/pdf") || file_exists("{$this->tmp}/public/pdf"));
        $this->assertDirectoryDoesNotExist($old);
        $this->actingAs($this->patron)->get("/read/$id")->assertOk();

        // Safe to run again.
        $this->artisan('fulltext:make-private')->expectsOutputToContain('Moved 0 full text(s)')->assertSuccessful();
    }

    public function test_the_move_command_handles_a_plain_public_pdf_folder()
    {
        $id = $this->record();
        $this->fullText($id, 'ft-b.pdf');
        File::ensureDirectoryExists("{$this->tmp}/public/pdf");
        file_put_contents("{$this->tmp}/public/pdf/ft-b.pdf", "%PDF-1.4\n%%EOF\n");

        $this->artisan('fulltext:make-private')->expectsOutputToContain('Moved 1 full text(s)')->assertSuccessful();

        $this->assertFileExists("{$this->tmp}/storage/app/private/pdf/ft-b.pdf");
        $this->assertDirectoryDoesNotExist("{$this->tmp}/public/pdf");
    }
}
