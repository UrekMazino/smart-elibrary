<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ViewCatalog;
use App\Models\Frontpage;

class GenerateCovers extends Command
{
    protected $signature = 'covers:placeholders
                            {--clear : Remove all generated placeholder covers (gen-*)}
                            {--force : Regenerate placeholders (clears existing gen-* first, keeps real covers)}';

    protected $description = 'Generate temporary title-based placeholder cover photos for published materials that have no real cover';

    public function handle()
    {
        if ($this->option('clear')) {
            return $this->clearGenerated();
        }

        if ($this->option('force')) {
            $this->clearGenerated(false);
        }

        // Materials that still have NO cover at all (real covers = cp-*, are preserved).
        $covered = Frontpage::whereNull('deleted_at')->pluck('catalog_id')->all();
        // ...and whose placeholder staff have not turned off (record page, catalogs.cover_placeholder).
        $turnedOff = \App\Models\Catalog::where('cover_placeholder', 0)->pluck('id')->all();
        $materials = ViewCatalog::where('is_published', 1)->whereNull('deleted_at')
            ->whereNotIn('catalog_id', $covered)
            ->whereNotIn('catalog_id', $turnedOff)
            ->get();

        if ($materials->isEmpty()) {
            $this->info('Every published material already has a cover — nothing to generate.');
            return self::SUCCESS;
        }

        $this->info("Generating placeholder covers for {$materials->count()} materials...");
        $bar = $this->output->createProgressBar($materials->count());
        $done = 0;

        foreach ($materials as $vc) {
            $file = 'gen-' . $vc->catalog_id . '.jpg';
            if (generatePlaceholderCover($vc, public_path('frontpages/' . $file))) {
                makeThumbnail(public_path('frontpages/' . $file), public_path('thumb/' . $file));
                Frontpage::create([
                    'catalog_id'        => $vc->catalog_id,
                    'FrontPageName'     => $file,
                    'FrontPageType'     => 'jpg',
                    'FrontPageLocation' => 'frontpages/' . $file,
                ]);
                $done++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Done — generated {$done} placeholder covers. Remove later with: php artisan covers:placeholders --clear");
        return self::SUCCESS;
    }

    /** Delete generated placeholder covers (files + records). Real covers (cp-*) are untouched. */
    protected function clearGenerated($report = true)
    {
        $gen = Frontpage::where('FrontPageName', 'like', 'gen-%')->get();
        foreach ($gen as $fp) {
            @unlink(public_path($fp->FrontPageLocation));
            @unlink(public_path('thumb/' . basename($fp->FrontPageLocation)));
            $fp->forceDelete();
        }
        if ($report) {
            $this->info("Removed {$gen->count()} generated placeholder covers.");
            return self::SUCCESS;
        }
    }
}
