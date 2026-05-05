<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ChairController;
use App\Http\Controllers\FloorController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

// ───── Public ─────
Route::get('/shops/{shop}/floors/{floor}/view', [FloorController::class, 'view'])->name('floors.view');

// ───── Guest only ─────
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// ───── Authenticated ─────
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/', [ShopController::class, 'index'])->name('shops.index');
    Route::post('/shops', [ShopController::class, 'store'])->name('shops.store');
    Route::get('/shops/{shop}', [ShopController::class, 'show'])->name('shops.show');
    Route::delete('/shops/{shop}', [ShopController::class, 'destroy'])->name('shops.destroy');

    Route::get('/shops/{shop}/floors/{floor}/edit', [FloorController::class, 'edit'])->name('floors.edit');
    Route::get('/shops/{shop}/floors/{floor}/operate', [FloorController::class, 'operate'])->name('floors.operate');

    Route::post('/api/shops/{shop}/floors', [FloorController::class, 'store'])->name('api.floors.store');
    Route::put('/api/floors/{floor}', [FloorController::class, 'update'])->name('api.floors.update');
    Route::delete('/api/floors/{floor}', [FloorController::class, 'destroy'])->name('api.floors.destroy');
    Route::post('/api/upload-image', [UploadController::class, 'image'])->name('api.upload-image');
    Route::post('/api/chairs/{chair}/toggle', [ChairController::class, 'toggle'])->name('api.chairs.toggle');
    Route::post('/api/floors/{floor}/chairs/reset', [ChairController::class, 'resetFloor'])->name('api.chairs.reset');
});
