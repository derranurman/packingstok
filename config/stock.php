<?php

return [
    // Jika true, stock boleh minus saat scan. Jika false, scan akan gagal kalau stok tidak cukup.
    'allow_negative' => env('ALLOW_NEGATIVE_STOCK', false),
];
