<?php

namespace App\Console\Commands;

use App\Services\OrderImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ImportWatchFolderCommand extends Command
{
    protected $signature = 'imports:watch
                            {--once : Proses file yang ada saat ini lalu keluar (default)}
                            {--loop : Loop manual tiap --interval detik (tidak butuh scheduler)}
                            {--interval=30 : Interval loop dalam detik (hanya untuk --loop)}';

    protected $description = 'Pantau folder inbox, otomatis import file CSV/XLSX yang di-drop.';

    public function handle(OrderImportService $service): int
    {
        if (! (bool) config('imports.enabled', true)) {
            $this->warn('Watch folder disabled (WATCH_FOLDER_ENABLED=false).');
            return self::SUCCESS;
        }

        if ($this->option('loop')) {
            return $this->runLoop($service);
        }

        return $this->runOnce($service);
    }

    private function runLoop(OrderImportService $service): int
    {
        $interval = max(5, (int) $this->option('interval'));
        $this->info("Mode loop, interval {$interval} detik. Ctrl+C untuk berhenti.");

        while (true) {
            $this->runOnce($service);
            sleep($interval);
        }
    }

    private function runOnce(OrderImportService $service): int
    {
        $inbox = $this->ensureDir(config('imports.inbox_path'));
        $processed = $this->ensureDir(config('imports.processed_path'));
        $failed = $this->ensureDir(config('imports.failed_path'));

        $allowed = (array) config('imports.allowed_extensions', ['csv', 'xlsx', 'xls', 'txt']);
        $minAge = (int) config('imports.min_file_age_seconds', 10);

        $files = $this->scanFiles($inbox, $allowed, $minAge);

        if (empty($files)) {
            // Hanya log verbose, tidak perlu menuh-menuhin log
            if ($this->output->isVerbose()) {
                $this->line("No new file in {$inbox}");
            }
            return self::SUCCESS;
        }

        $this->info('Found '.count($files).' file(s) to process in '.$inbox);

        $totalOk = 0;
        $totalFail = 0;

        foreach ($files as $path) {
            $basename = basename($path);
            try {
                $result = $service->import($path, $basename, null, 'watch_folder');
                $this->moveTo($path, $processed);

                $msg = sprintf(
                    '[OK] %s  rows=%d, created=%d, updated=%d, skipped=%d',
                    $basename,
                    $result['rows'],
                    $result['created'],
                    $result['updated'],
                    $result['skipped']
                );
                $this->info($msg);
                Log::info('Watch folder import OK', [
                    'file' => $basename,
                    'created' => $result['created'],
                    'updated' => $result['updated'],
                    'skipped' => $result['skipped'],
                ]);
                $totalOk++;
            } catch (\Throwable $e) {
                $this->moveTo($path, $failed, $e->getMessage());
                $this->error('[FAIL] '.$basename.' — '.$e->getMessage());
                Log::error('Watch folder import FAIL', [
                    'file' => $basename,
                    'error' => $e->getMessage(),
                ]);
                $totalFail++;
            }
        }

        $this->line(sprintf('Done. ok=%d, fail=%d', $totalOk, $totalFail));
        return $totalFail > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Scan folder untuk file yang sudah "diam" lama (supaya tidak ambil file
     * yang masih lagi di-upload/di-sync Google Drive).
     *
     * @return string[] absolute paths
     */
    private function scanFiles(string $inbox, array $allowed, int $minAge): array
    {
        if (! is_dir($inbox)) {
            return [];
        }

        $now = time();
        $files = [];

        foreach (File::files($inbox) as $info) {
            $path = $info->getPathname();
            $ext = strtolower($info->getExtension());
            $basename = $info->getFilename();

            if (! in_array($ext, $allowed, true)) continue;
            if (str_starts_with($basename, '.')) continue;
            // Google Drive / Dropbox biasanya pakai pattern ini buat file yg lagi di-download
            if (preg_match('/\.(crdownload|part|tmp|download)$/i', $basename)) continue;

            $mtime = $info->getMTime();
            if (($now - $mtime) < $minAge) continue;

            $files[$path] = $mtime;
        }

        asort($files); // urutkan dari yg paling tua
        return array_keys($files);
    }

    private function ensureDir(string $path): string
    {
        if (! is_dir($path)) {
            File::makeDirectory($path, 0775, true, true);
        }
        return $path;
    }

    /**
     * Pindah file ke folder target. Tambahkan timestamp prefix supaya unik.
     * Kalau $errorMessage diisi, tulis file companion .error.txt di sebelahnya.
     */
    private function moveTo(string $source, string $targetDir, ?string $errorMessage = null): void
    {
        $basename = basename($source);
        $timestamp = date('Ymd_His');
        $target = rtrim($targetDir, '/\\').DIRECTORY_SEPARATOR.$timestamp.'__'.$basename;

        // Kalau target sudah ada (rare), append random
        if (file_exists($target)) {
            $target .= '.'.substr(md5(uniqid()), 0, 6);
        }

        @rename($source, $target);

        if ($errorMessage !== null) {
            @file_put_contents($target.'.error.txt', $errorMessage.PHP_EOL);
        }
    }
}
