<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MappingController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WatchFolderController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Packing\PackingController;
use Illuminate\Support\Facades\Route;

// Root redirect ke login atau dashboard sesuai role
Route::get('/', function () {
    if (! auth()->check()) return redirect()->route('login');
    return auth()->user()->isAdmin()
        ? redirect()->route('admin.dashboard')
        : redirect()->route('packing.index');
});

// Auth
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:10,1');
});
Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')->name('logout');

// Admin area
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::resource('products', ProductController::class)->except(['show']);

    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/import', [OrderController::class, 'importForm'])->name('orders.import.form');
    Route::post('orders/import', [OrderController::class, 'import'])->name('orders.import');
    Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');

    Route::get('mappings', [MappingController::class, 'index'])->name('mappings.index');
    Route::post('mappings/{item}', [MappingController::class, 'update'])->name('mappings.update');

    Route::get('watch-folder', [WatchFolderController::class, 'index'])->name('watchfolder.index');
    Route::post('watch-folder/run', [WatchFolderController::class, 'runNow'])->name('watchfolder.run');

    Route::resource('users', UserController::class)->except(['show']);
});

// Packer area (admin juga boleh akses)
Route::middleware(['auth', 'role:admin,packer'])->prefix('packing')->name('packing.')->group(function () {
    Route::get('/', [PackingController::class, 'index'])->name('index');
    Route::post('/scan', [PackingController::class, 'scan'])->name('scan');
});
