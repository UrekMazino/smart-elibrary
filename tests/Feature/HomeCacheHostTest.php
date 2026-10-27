<?php

namespace Tests\Feature;

use App\Models\Frontpage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * The homepage keeps its shelves and ranked carousels in the cache for 10 minutes. They used to keep whole addresses,
 * made on the host of the visit that filled the cache: after one visit on 127.0.0.1 every cover broke on localhost,
 * as the Content Security Policy loads images from the page's own host only. They keep paths now (withSiteUrls).
 */
class HomeCacheHostTest extends TestCase
{
    use RefreshDatabase;

    public function test_cached_covers_and_links_follow_the_host_of_each_visit()
    {
        $material = DB::table('materials')->insertGetId(['name' => 'Book']);
        $id = DB::table('catalogs')->insertGetId(['material_type_id' => $material, 'field_data' => '[]', 'is_published' => 1]);
        DB::table('view_catalogs')->insert(['catalog_id' => $id, 'title_statement' => 'Rice farming', 'material_type_id' => $material, 'is_published' => 1, 'created_at' => now()]);
        Frontpage::create(['catalog_id' => $id, 'FrontPageName' => 'hc-cover.jpg', 'FrontPageLocation' => 'frontpages/hc-cover.jpg', 'FrontPageType' => 'jpg']);
        saveView($id);

        URL::forceRootUrl('http://127.0.0.1:8000');   // a visit that fills the caches
        getShelves(45);
        getMostViewed(10);

        URL::forceRootUrl('http://localhost:8000');   // the next visitor, on another host
        $shelved = getShelves(45)[0]['items'][0];
        $viewed = getMostViewed(10)[0];

        $this->assertSame('http://localhost:8000/frontpages/hc-cover.jpg', $shelved['thumb']);
        $this->assertStringStartsWith('http://localhost:8000/full-description/', $shelved['url']);
        $this->assertSame('http://localhost:8000/frontpages/hc-cover.jpg', $viewed['thumb']);
        $this->assertStringStartsWith('http://localhost:8000/full-description/', $viewed['url']);
    }
}
