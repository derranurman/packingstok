<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrderImport;
use App\Services\OrderImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class WatchFolderController extends Controller
{
    public function index(): View
    {
        $enabled = (bool) config('imports.enabled', true);
        $inbox = (string) config('imports.inbox_path');
        $processed = (string) config('imports.processed_path');
        $failed = (string) config('imports.failed_path');
        $minAge = (int) config('imports.min_file_age_seconds', 10);
        $allowed = (array) config('imports.allowed_extensions', []);

        $inboxFiles = $this->listFiles($inbox);
        $failedFiles = $this->listFiles($failed, withErrorSidecar: true);

        $recentAutoImports = OrderImport::where('source', 'watch_folder')
            ->latest()
            ->limit(15)
            ->get();

        $lastRun = $recentAutoImports->first()?->created_at;

        return view('admin.watch_folder.index', compact(
            'enabled', 'inbox', 'processed', 'failed', 'minAge', 'allowed',
            'inboxFiles', 'failedFiles', 'recentAutoImports', 'lastRun'
        ));
    }

    public function runNow(OrderImportService $service): RedirectResponse
    {
        if (! (bool) config('imports.enabled', true)) {
            return back()->withErrors(['run' => 'Watch folder dinonaktifkan di .env (WATCH_FOLDER_ENABLED=false).']);
        }

        // Jalankan command sync, capture output sederhana
        $exit = \Illuminate\Support\Facades\Artisan::call('imports:watch', ['--once' => true]);
        $output = trim(\Illuminate\Support\Facades\Artisan::output());

        return back()->with('success', 'Run selesai (exit '.$exit.'). '.$output);
    }

    /**
     * @return array<int,array{name:string, size:int, mtime:int, error?:string}>
     */
    private function listFiles(string $dir, bool $withErrorSidecar = false): array
    {
        if (! is_dir($dir)) return [];

        $out = [];
        foreach (File::files($dir) as $info) {
            $name = $info->getFilename();
            if (str_ends_with($name, '.error.txt')) continue;

            $entry = [
                'name' => $name,
                'size' => $info->getSize(),
                'mtime' => $info->getMTime(),
            ];

            if ($withErrorSidecar) {
                $errPath = $info->getPathname().'.error.txt';
                if (is_file($errPath)) {
                    $entry['error'] = trim((string) @file_get_contents($errPath));
                }
            }

            $out[] = $entry;
        }

        usort($out, fn ($a, $b) => $b['mtime'] <=> $a['mtime']);
        return $out;
    }
}
