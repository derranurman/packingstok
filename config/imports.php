<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Watch Folder
    |--------------------------------------------------------------------------
    | Folder yang dipantau oleh command `imports:watch`. File CSV/XLSX yang
    | di-drop ke folder `inbox_path` akan otomatis di-import, lalu dipindah
    | ke `processed_path` (sukses) atau `failed_path` (error).
    |
    | Semua path BOLEH absolut (contoh: /home/toko/inbox atau C:\inbox)
    | atau relatif terhadap base_path() project.
    */

    'enabled' => (bool) env('WATCH_FOLDER_ENABLED', true),

    'inbox_path' => env('WATCH_FOLDER_INBOX', storage_path('app/import/inbox')),
    'processed_path' => env('WATCH_FOLDER_PROCESSED', storage_path('app/import/processed')),
    'failed_path' => env('WATCH_FOLDER_FAILED', storage_path('app/import/failed')),

    // Ekstensi file yang diterima
    'allowed_extensions' => ['csv', 'xlsx', 'xls', 'txt'],

    // Minimal umur file (detik) sebelum diproses. Supaya file yg lagi
    // di-upload/di-sync Google Drive tidak dibaca setengah jalan.
    'min_file_age_seconds' => (int) env('WATCH_FOLDER_MIN_AGE', 10),
];
