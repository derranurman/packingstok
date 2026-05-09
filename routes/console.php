<?php

use Illuminate\Support\Facades\Schedule;

// Jalankan polling TikTok tiap menit (saat mode=live)
Schedule::command('tiktok:poll')->everyMinute()->withoutOverlapping();
