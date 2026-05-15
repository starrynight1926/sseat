<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ChairController;
use App\Http\Controllers\FloorController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

// ───── Public ─────
Route::get('/shops/{shop}/floors/{floor}/view', [FloorController::class, 'view'])->name('floors.view');
Route::get('/api/floors/{floor}/chairs/statuses', [ChairController::class, 'statuses'])->name('api.chairs.statuses');

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

    // Trang chủ: dispatch theo role
    Route::get('/', function () {
        $user = auth()->user();
        if ($user->canManageUsers()) return redirect()->route('admin.dashboard');
        if ($user->canOperate() || $user->canBuild()) return redirect()->route('shops.index');
        return redirect()->route('login');
    })->name('home');

    // ── Shop browsing: cashier + builder + admin + super_admin ──
    Route::middleware('role:cashier,builder,admin,super_admin')->group(function () {
        Route::get('/shops', [ShopController::class, 'index'])->name('shops.index');
        Route::get('/shops/{shop}', [ShopController::class, 'show'])->name('shops.show');
    });

    // ── Builder + Super Admin: create/edit shops & floors ──
    Route::middleware('role:builder,super_admin')->group(function () {
        Route::post('/shops', [ShopController::class, 'store'])->name('shops.store');
        Route::delete('/shops/{shop}', [ShopController::class, 'destroy'])->name('shops.destroy');

        Route::get('/shops/{shop}/floors/{floor}/edit', [FloorController::class, 'edit'])->name('floors.edit');
        Route::post('/floors/{floor}/submit', [FloorController::class, 'submit'])->name('floors.submit');

        Route::post('/api/shops/{shop}/floors', [FloorController::class, 'store'])->name('api.floors.store');
        Route::put('/api/floors/{floor}', [FloorController::class, 'update'])->name('api.floors.update');
        Route::delete('/api/floors/{floor}', [FloorController::class, 'destroy'])->name('api.floors.destroy');
        Route::post('/api/upload-image', [UploadController::class, 'image'])->name('api.upload-image');
    });

    // ── Operate: cashier + builder + admin + super_admin ──
    Route::middleware('role:cashier,builder,admin,super_admin')->group(function () {
        Route::get('/shops/{shop}/floors/{floor}/operate', [FloorController::class, 'operate'])->name('floors.operate');
        Route::post('/api/chairs/{chair}/toggle', [ChairController::class, 'toggle'])->name('api.chairs.toggle');
        Route::post('/api/floors/{floor}/chairs/reset', [ChairController::class, 'resetFloor'])->name('api.chairs.reset');
    });

    // ── Admin panel: manager + admin + super_admin ──
    Route::prefix('admin')->name('admin.')->middleware('role:manager,admin,super_admin')->group(function () {
        Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/floors', [AdminController::class, 'floors'])->name('floors');
        Route::post('/floors/{floor}/approve', [AdminController::class, 'approveFloor'])->name('floors.approve');
        Route::post('/floors/{floor}/reject', [AdminController::class, 'rejectFloor'])->name('floors.reject');
        Route::post('/floors/{floor}/lock', [AdminController::class, 'lockFloor'])->name('floors.lock');

        // User management: admin + super_admin only
        Route::middleware('role:admin,super_admin')->group(function () {
            Route::get('/users', [AdminController::class, 'users'])->name('users');
            Route::patch('/users/{user}', [AdminController::class, 'updateUser'])->name('users.update');
            Route::post('/users/{user}/reset-password', [AdminController::class, 'resetUserPassword'])->name('users.reset-password');
        });

        // Shop management + audit logs: super_admin
        Route::middleware('role:super_admin')->group(function () {
            Route::get('/shops', [AdminController::class, 'shops'])->name('shops');
            Route::delete('/shops/{shop}', [AdminController::class, 'destroyShop'])->name('shops.destroy');
        });

        Route::get('/audit-logs', [AdminController::class, 'auditLogs'])->name('audit-logs');
    });
});
