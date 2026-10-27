<?php

namespace Database\Seeders;

use App\Models\News;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Sample featured news for designing the OPAC home's NEWS section (development only). Not part of
 * DatabaseSeeder: run it on its own after migrating,
 *   php artisan db:seed --class=NewsSampleSeeder
 *
 * Covers come from photos in the repo (public/opac/img) and go through the same steps as an admin
 * upload: the cover, its thumbnail and the NEWS section's 800 px WebP copy. Each body ends with a
 * line saying it is a sample. Running it again adds nothing: posts whose title already exists
 * (deleted ones too) are left alone. Refuses to run in production.
 */
class NewsSampleSeeder extends Seeder
{
    private const NOTE = '<p><em>Sample post for the NEWS section prototype.</em></p>';

    /** [days ago, title, cover in public/opac/img (or null), external link (or null), paragraphs] */
    private const POSTS = [
        [0, 'Ask the library a question: AI mode answers from the collection', 'about_1.jpg', 'https://example.org/', [
            'The eLibrary can now answer questions in plain language. Switch the search box to AI mode, type a question such as "How is African swine fever controlled?", and the library reads the most relevant pages of its own research collection before writing a short answer.',
            'Every answer cites its sources. Each number in the text links to the record it came from, so you can open the full report and check the passage yourself. When the collection has nothing on a question, AI mode says so instead of guessing.',
            'Questions in Filipino are answered in Filipino. AI mode is meant as a starting point: the records it cites are where the research is.',
        ]],
        [2, 'Browse the shelves from anywhere', 'hero_home_bg_fprdi.jpg', null, [
            'The shelf browser on the home page shows the collection the way it stands in the reading room: book spines on wooden shelves, grouped by subject. Slide along a shelf to see what sits next to a title you know.',
            'Hover over a spine to see its title and author, and open it for the full record. Browsing by shelf often turns up reports that a keyword search would miss, because they are filed beside the ones you already use.',
        ]],
        [6, 'Keep a list of the records you need', 'hero_home_bg_1.jpg', null, [
            'Signed-in readers can save any record with the star on its cover. Saved records stay in your list until you remove them, on any device you sign in from.',
            'Use the list to gather sources for a thesis or a project, then come back to read, download or cite them. The Saved button under the search box shows how many records you have kept.',
        ]],
        [13, 'Looking for research on food and nutrition?', null, 'https://example.org/', [
            'The collection holds technical reports, theses and journal articles on food composition, labelling and nutrition. Start with a subject search, then narrow the results with the filters for material type and year.',
            'Advanced search lets you combine fields, for example a subject with words in the title, and exclude what you do not need. Records with full text can be read online after signing in.',
        ]],
        [21, 'Annual reports, searchable page by page', 'hero_home_bg_fprdi2.jpg', null, [
            'Decades of annual reports have been scanned and read into the library\'s search index. A search now finds words inside the reports, not only in their titles, and AI mode can quote the page an answer comes from.',
            'Older reports are a record of which studies were funded, who led them and what they found. They are often the only place where results of smaller projects were published.',
        ]],
        [34, 'Cite a record in one click', null, null, [
            'Every record has a Cite button that formats the reference for you. Choose the style your adviser or journal asks for and copy the result into your paper.',
            'The citation is built from the catalogue record, so check names and dates against the title page before you submit. If something in a record looks wrong, the library staff would like to know: send them a message from the record page.',
            'Citing what you read, and only what you read, keeps your work honest and helps the next reader find the same sources.',
        ]],
    ];

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('NewsSampleSeeder adds sample posts for development; not run in production.');
            return;
        }

        $author = User::whereIn('usertype', ['Developer', 'Administrator'])->value('id');
        $added = 0;
        foreach (self::POSTS as [$days, $title, $photo, $link, $paragraphs]) {
            if (News::withTrashed()->where('news_title', $title)->exists()) continue;

            $news = new News;
            $news->news_title = $title;
            $news->news_body = cleanNewsHtml(implode('', array_map(fn ($p) => '<p>' . e($p) . '</p>', $paragraphs)) . self::NOTE);
            $news->news_cover = $photo ? $this->cover(public_path("opac/img/$photo")) : null;
            $news->redirect_url = $link;
            $news->featured = 'Yes';
            $news->created_by = $author;
            $news->created_at = $news->updated_at = now()->subDays($days)->setTime(9, 30);
            $news->save();
            $added++;
        }

        cache()->forget('home_latest_news');
        $this->command?->info("Sample news posts added: $added (of " . count(self::POSTS) . ').');
    }

    /** Store a photo as an uploaded cover would be: the cover (at most 1600 px, as a 2 MB upload would be), its thumbnail and newspaper copy. */
    private function cover(string $source): ?string
    {
        if (!is_file($source) || !ensurePublicDir(public_path('news_cover'))) return null;

        $name = 'news-' . uniqid() . '.jpg';
        $dest = public_path("news_cover/$name");
        if (!makeThumbnail($source, $dest, 1600)) return null;
        makeThumbnail($dest, public_path("thumb/$name"));
        makeNewsPrintCopy($dest);

        return "news_cover/$name";
    }
}
