<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::redirect('/', '/login');

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AdvertController;
use App\Http\Controllers\ManagerController;
use App\Http\Controllers\LogisticsController;
use App\Http\Controllers\PlansController;
use App\Http\Controllers\SyncController;

Route::get('/debug-db', function () {
    $order = \App\Models\OrderRaw::whereNotNull('srid')->first();
    $ws = \App\Models\WarehouseStock::first();
    return response()->json([
        'order' => $order,
        'ws' => $ws
    ]);
});

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::post('/stores/switch', [StoreController::class, 'switch'])->name('stores.switch');
    Route::resource('stores', StoreController::class)->only(['index', 'store', 'destroy']);
    Route::resource('products', ProductController::class)->only(['index', 'show']);
    Route::resource('orders', OrderController::class)->only(['index']);
    Route::get('/logistics', [LogisticsController::class, 'index'])->name('logistics.index');
    Route::resource('analytics', AnalyticsController::class)->only(['index']);
    Route::resource('adverts', AdvertController::class)->only(['index']);
    Route::post('adverts/external', [AdvertController::class, 'storeExternal'])->name('adverts.external.store');
    Route::resource('managers', ManagerController::class)->only(['index', 'show', 'store']);
    Route::post('managers/{manager}/bind', [ManagerController::class, 'bindProducts'])->name('managers.bind');
    Route::patch('managers/{manager}/permissions', [ManagerController::class, 'updatePermissions'])->name('managers.permissions.update');
    Route::post('managers/{manager}/stores', [ManagerController::class, 'bindStores'])->name('managers.stores.bind');
    Route::resource('plans', PlansController::class)->only(['index', 'show', 'store']);
    Route::get('/sync', [SyncController::class, 'index'])->name('sync.index');
    Route::post('/sync/run', [SyncController::class, 'run'])->name('sync.run');
    Route::post('/sync/schedules', [SyncController::class, 'schedule'])->name('sync.schedules.store');
    Route::patch('/sync/schedules/{schedule}/toggle', [SyncController::class, 'toggle'])->name('sync.schedules.toggle');
    Route::delete('/sync/schedules/{schedule}', [SyncController::class, 'destroy'])->name('sync.schedules.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/products', [ProfileController::class, 'updateProducts'])->name('profile.products.update');
});

require __DIR__.'/auth.php';
