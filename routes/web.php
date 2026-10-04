<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\SectionController;
use App\Http\Controllers\Admin\UpdateController;
use App\Http\Controllers\CvDownloadController;
use App\Http\Controllers\LandingController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('home');
Route::get('/cv/unduh', CvDownloadController::class)->name('cv.download');

Route::prefix('admin/api')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

    Route::middleware('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::get('/dashboard', DashboardController::class);
        Route::get('/updates', [UpdateController::class, 'show']);
        Route::post('/updates/check', [UpdateController::class, 'check'])->middleware('throttle:12,1');
        Route::post('/updates', [UpdateController::class, 'store'])->middleware('throttle:5,10');
        Route::get('/profile', [ProfileController::class, 'show']);
        Route::post('/profile', [ProfileController::class, 'update']);

        Route::get('/sections', [SectionController::class, 'index']);
        Route::post('/sections/{section}', [SectionController::class, 'update']);
        Route::post('/sections/{section}/move', [SectionController::class, 'move']);

        $types = 'social-links|experiences|educations|skills|projects|testimonials';

        Route::get('/content/{type}', [ContentController::class, 'index'])->where('type', $types);
        Route::post('/content/{type}', [ContentController::class, 'store'])->where('type', $types);
        Route::post('/content/{type}/{id}', [ContentController::class, 'update'])->where('type', $types)->whereNumber('id');
        Route::delete('/content/{type}/{id}', [ContentController::class, 'destroy'])->where('type', $types)->whereNumber('id');
        Route::post('/content/{type}/{id}/move', [ContentController::class, 'move'])->where('type', $types)->whereNumber('id');
        Route::post('/content/{type}/{id}/visibility', [ContentController::class, 'visibility'])->where('type', $types)->whereNumber('id');
    });
});

Route::view('/admin/login', 'admin')->middleware('guest')->name('admin.login');

Route::view('/admin/{path?}', 'admin')
    ->where('path', '^(?!api(?:/|$)).*$')
    ->middleware('auth')
    ->name('admin.home');
