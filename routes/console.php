<?php

use Illuminate\Support\Facades\Schedule;

// Auto-import file yang di-drop ke folder inbox (config/imports.php).
// Jalan tiap 2 menit selama WATCH_FOLDER_ENABLED=true dan scheduler aktif
// (di laptop: Task Scheduler / cron panggil `php artisan schedule:run` tiap menit).
Schedule::command('imports:watch --once')
    ->everyTwoMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->when(fn () => (bool) config('imports.enabled', true));
