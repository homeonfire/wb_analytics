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
    Route::resource('managers', ManagerController::class)->only(['index', 'store']);
    Route::post('managers/{manager}/bind', [ManagerController::class, 'bindProducts'])->name('managers.bind');
    Route::resource('plans', PlansController::class)->only(['index', 'store']);
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
