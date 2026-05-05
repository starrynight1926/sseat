<?php

use App\Http\Controllers\FloorController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

// Home = shops index
Route::get('/', [ShopController::class, 'index'])->name('shops.index');
Route::post('/shops', [ShopController::class, 'store'])->name('shops.store');
Route::get('/shops/{shop}', [ShopController::class, 'show'])->name('shops.show');
Route::delete('/shops/{shop}', [ShopController::class, 'destroy'])->name('shops.destroy');

// Editor / Viewer per floor
Route::get('/shops/{shop}/floors/{floor}/edit', [FloorController::class, 'edit'])->name('floors.edit');
Route::get('/shops/{shop}/floors/{floor}/view', [FloorController::class, 'view'])->name('floors.view');

// API
Route::post('/api/shops/{shop}/floors', [FloorController::class, 'store'])->name('api.floors.store');
Route::put('/api/floors/{floor}', [FloorController::class, 'update'])->name('api.floors.update');
Route::delete('/api/floors/{floor}', [FloorController::class, 'destroy'])->name('api.floors.destroy');
Route::post('/api/upload-image', [UploadController::class, 'image'])->name('api.upload-image');
