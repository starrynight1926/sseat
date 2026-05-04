<?php

use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('editor'))->name('editor');
Route::get('/view', fn () => view('viewer'))->name('viewer');

Route::post('/api/upload-image', [UploadController::class, 'image'])->name('api.upload-image');
