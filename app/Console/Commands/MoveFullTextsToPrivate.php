<?php

namespace App\Console\Commands;

use App\Models\Multimedia;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Moves the full-text PDFs out of the web server's reach, once per installation (safe to re-run).
 *
 * They used to live in storage/app/public/pdf, served as /pdf/... (the public/pdf link) and as
 * /storage/pdf/... (the public/storage link): anyone with a file's address got it, without the
 * sign-in and published-record checks of Read and Download. They now live on the private disk
 * (storage/app/private/pdf). multimedia.FileLocation ("pdf/ft-….pdf") is unchanged: the same
 * value now resolves there (fullTextPath()).
 */
class MoveFullTextsToPrivate extends Command
{
    protected $signature = 'fulltext:make-private {--dry-run : Report what would move, change nothing}';

    protected $description = 'Move full-text PDFs from the public folders into private storage (storage/app/private/pdf)';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $dest = Storage::disk('private')->path('pdf');
        if (! $dry) File::ensureDirectoryExists($dest);

        // public/pdf is normally a link into storage/app/public/pdf; on some installs it is a real folder.
        $link = public_path('pdf');
        $sources = collect([$link, storage_path('app/public/pdf')])
            ->filter(fn ($dir) => is_dir($dir))
            ->map(fn ($dir) => realpath($dir))
            ->unique()->values();

        $moved = $duplicates = 0;
        $conflicts = [];
        foreach ($sources as $source) {
            foreach (File::files($source) as $file) {   // files only; dotfiles are skipped
                $target = $dest . DIRECTORY_SEPARATOR . $file->getFilename();

                if (is_file($target)) {
                    // Already moved (an earlier, interrupted run): drop the public copy if identical.
                    if (md5_file($target) === md5_file($file->getPathname())) {
                        $duplicates++;
                        if (! $dry) File::delete($file->getPathname());
                    } else {
                        $conflicts[] = $file->getPathname();
                    }
                    continue;
                }

                $moved++;
                if (! $dry && ! File::move($file->getPathname(), $target)) {
                    // A move across drives can fail: copy, then remove the public copy.
                    if (! File::copy($file->getPathname(), $target)) {
                        $this->error("Could not move {$file->getPathname()}");
                        return self::FAILURE;
                    }
                    File::delete($file->getPathname());
                }
            }
        }

        $verb = $dry ? 'Would move' : 'Moved';
        $this->info("$verb $moved full text(s) to $dest" . ($duplicates ? "; $duplicates already there" : '') . '.');

        if ($conflicts) {
            $this->error(count($conflicts) . ' file(s) differ from a private file of the same name; left in place:');
            foreach ($conflicts as $path) $this->line("  $path");
            return self::FAILURE;
        }

        if (! $dry) {
            // The public/pdf link goes (it is no longer in config/filesystems.php `links`), then the
            // emptied folders. A folder still holding something is kept and reported.
            if (is_link($link)) {
                @unlink($link) || @rmdir($link);   // rmdir: a Windows directory link
            }
            foreach ($sources as $source) {
                if (is_dir($source) && count(File::allFiles($source, true)) === 0 && count(File::directories($source)) === 0) {
                    File::deleteDirectory($source);
                } elseif (is_dir($source)) {
                    $this->warn("Kept $source: it still holds other files.");
                }
            }
            if (file_exists($link)) $this->warn("$link still exists: remove it so nothing under public/ points at full texts.");
        }

        // Every current full text should now be found where the app looks for it.
        $missing = Multimedia::get(['catalog_id', 'FileLocation'])
            ->filter(fn ($m) => ! (($path = fullTextPath($m->FileLocation)) && is_file($path)))
            ->pluck('catalog_id');
        if ($missing->isNotEmpty() && ! $dry) {
            $this->warn("{$missing->count()} record(s) have a full-text row but no file in private storage (catalog ids: "
                . $missing->take(20)->implode(', ') . ($missing->count() > 20 ? ', …' : '') . ').');
        }

        return self::SUCCESS;
    }
}
