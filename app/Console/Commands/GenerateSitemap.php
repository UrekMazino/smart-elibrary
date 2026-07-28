<?php

namespace App\Console\Commands;

use App\Models\ViewCatalog;
use Illuminate\Console\Command;

/**
 * Build the OPAC sitemap (and robots.txt) — OPAC_SEO_PLAN.md workstream C.
 *
 * Without a sitemap a crawler has to reach every record by following links; deep catalogue pages
 * are easily missed. This writes static files so serving them costs nothing at request time, and
 * uses a sitemap index from the start: one chunk is enough for ~7.6k records, but the structure
 * survives growth past the 50,000-URL-per-file limit without a rewrite.
 *
 * robots.txt is generated here too so its absolute `Sitemap:` line always matches APP_URL
 * (it differs between the dev host and production).
 */
class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate
                            {--chunk=25000 : URLs per sitemap file (spec max 50,000)}
                            {--no-robots : Do not rewrite public/robots.txt}';

    protected $description = 'Generate public/sitemap.xml (+ chunks) for published catalogue records, and robots.txt';

    /**
     * Private / gated / non-content paths that must never be crawled.
     *
     * CAREFUL: robots.txt matches by PREFIX. Never add '/full-description' here — it would block
     * every record page (`/full-description/10435`) and hide the whole catalogue from search.
     * The id-less `/full-description` form is harmless anyway; it just redirects home.
     */
    private const DISALLOW = [
        '/admin', '/read/', '/download', '/profile', '/transactions', '/inquiries',
        '/to-review', '/review', '/reservation/', '/inquiry/',
        '/search/suggest', '/search/ai-answer', '/login', '/register', '/password',
    ];

    public function handle(): int
    {
        $chunkSize = max(1, min((int) $this->option('chunk'), 50000));
        $public = public_path();
        $files = [];

        // --- static pages (only genuinely indexable ones; /results is noindex) ---
        $files[] = $this->writeUrlset("$public/sitemap-pages.xml", [
            ['loc' => url('/'), 'lastmod' => null],
        ]);

        // --- record pages ---
        $query = ViewCatalog::where('is_published', 1)->whereNull('deleted_at');
        $total = (clone $query)->count();
        $this->info("Published records: " . number_format($total));

        $part = 0;
        $buffer = [];
        $written = 0;

        // chunkById keeps memory flat as the catalogue grows.
        $query->select('catalog_id', 'title_statement', 'updated_at')->orderBy('catalog_id')
            ->chunkById($chunkSize, function ($rows) use (&$part, &$buffer, &$files, &$written, $public, $chunkSize) {
                foreach ($rows as $r) {
                    $buffer[] = [
                        'loc'     => recordUrl($r),
                        'lastmod' => $r->updated_at ? $r->updated_at->toAtomString() : null,
                    ];
                    if (count($buffer) >= $chunkSize) {
                        $part++;
                        $files[] = $this->writeUrlset("$public/sitemap-records-{$part}.xml", $buffer);
                        $written += count($buffer);
                        $buffer = [];
                    }
                }
            }, 'catalog_id');

        if ($buffer) {
            $part++;
            $files[] = $this->writeUrlset("$public/sitemap-records-{$part}.xml", $buffer);
            $written += count($buffer);
        }

        // --- the index ---
        $this->writeIndex("$public/sitemap.xml", $files);

        // Drop stale chunk files from a previous, larger run.
        foreach (glob("$public/sitemap-records-*.xml") ?: [] as $existing) {
            if (! in_array(basename($existing), array_map('basename', $files), true)) {
                @unlink($existing);
                $this->line('  removed stale ' . basename($existing));
            }
        }

        $this->info("Wrote {$written} record URLs across {$part} file(s) + sitemap.xml");

        if (! $this->option('no-robots')) {
            $this->writeRobots("$public/robots.txt");
            $this->info('Wrote robots.txt');
        }

        return self::SUCCESS;
    }

    /** @param array<int,array{loc:string,lastmod:?string}> $urls */
    private function writeUrlset(string $path, array $urls): string
    {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
             . "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
        foreach ($urls as $u) {
            $xml .= "  <url>\n    <loc>" . htmlspecialchars($u['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</loc>\n";
            if (! empty($u['lastmod'])) {
                $xml .= "    <lastmod>{$u['lastmod']}</lastmod>\n";
            }
            $xml .= "  </url>\n";
        }
        $xml .= "</urlset>\n";

        file_put_contents($path, $xml);

        return $path;
    }

    /** @param array<int,string> $files */
    private function writeIndex(string $path, array $files): void
    {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
             . "<sitemapindex xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
        foreach ($files as $f) {
            $loc = url('/' . basename($f));
            $xml .= "  <sitemap>\n    <loc>" . htmlspecialchars($loc, ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</loc>\n"
                  . "    <lastmod>" . now()->toAtomString() . "</lastmod>\n  </sitemap>\n";
        }
        $xml .= "</sitemapindex>\n";

        file_put_contents($path, $xml);
    }

    private function writeRobots(string $path): void
    {
        $lines = ["# Generated by: php artisan sitemap:generate", '', 'User-agent: *'];
        foreach (self::DISALLOW as $rule) {
            $lines[] = 'Disallow: ' . $rule;
        }
        $lines[] = '';
        $lines[] = 'Sitemap: ' . url('/sitemap.xml');
        $lines[] = '';

        file_put_contents($path, implode("\n", $lines));
    }
}
