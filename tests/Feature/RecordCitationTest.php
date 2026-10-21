<?php

namespace Tests\Feature;

use App\Models\ViewCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** The record page's Citation box offers APA, MLA and Chicago: one shows, the others are a click away. */
class RecordCitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_record_page_offers_three_citation_styles()
    {
        $material = DB::table('materials')->insertGetId(['name' => 'Book']);
        $id = DB::table('catalogs')->insertGetId(['material_type_id' => $material, 'field_data' => '[]', 'is_published' => 1]);
        DB::table('view_catalogs')->insert(['catalog_id' => $id, 'title_statement' => 'Philippine nutrition facts and figures',
            'joint_authors' => 'Cruz, Juan', 'publisher' => 'FNRI', 'pub_year' => '2024', 'material_type_id' => $material, 'is_published' => 1]);

        $page = $this->get("/full-description/$id");
        $html = $this->get($page->headers->get('Location'))->assertOk()->getContent();   // canonical slug URL

        // One switch button per style, APA chosen.
        preg_match_all('#<button type="button" class="fd-cite-style" data-cite-style="([^"]+)" aria-pressed="(true|false)"#', $html, $buttons, PREG_SET_ORDER);
        $this->assertSame([['APA', 'true'], ['MLA', 'false'], ['Chicago', 'false']], array_map(fn ($b) => [$b[1], $b[2]], $buttons));

        // Each style's citation is on the page; only APA is showing.
        $citations = buildCitations(ViewCatalog::where('catalog_id', $id)->first());
        preg_match_all('#<p class="fd-cite-text" data-cite-style="([^"]+)"( hidden)?>(.*?)</p>#s', $html, $texts, PREG_SET_ORDER);
        $this->assertSame(
            [['APA', false, $citations['APA']], ['MLA', true, $citations['MLA']], ['Chicago', true, $citations['Chicago']]],
            array_map(fn ($t) => [$t[1], $t[2] !== '', $t[3]], $texts)
        );
        $this->assertStringContainsString('<em>Philippine Nutrition Facts and Figures</em>', $citations['MLA']);   // headline capitals
    }
}
