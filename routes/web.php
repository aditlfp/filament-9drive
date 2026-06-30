<?php

use App\Http\Controllers\FileDownloadController;
use App\Http\Controllers\FilePreviewController;
use App\Http\Controllers\GoogleDriveConnectionController;
use App\Http\Controllers\ShareController;
use Filament\Http\Middleware\Authenticate as FilamentAuthenticate;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return to_route('filament.admin.auth.login');
});

Route::middleware([FilamentAuthenticate::class])->prefix('admin/google-drive')->name('google-drive.')->group(function () {
    Route::get('connect', [GoogleDriveConnectionController::class, 'redirect'])->name('connect');
    Route::get('callback', [GoogleDriveConnectionController::class, 'callback'])->name('callback');
});

Route::middleware([FilamentAuthenticate::class])->group(function () {
    Route::get('/preview/image/{file}', [FilePreviewController::class, 'image'])
        ->name('file.preview.image');
    Route::get('/files/{file}/download', [FileDownloadController::class, 'show'])
        ->name('file.download');
    Route::post('/files/download', [FileDownloadController::class, 'bulk'])
        ->name('file.download.bulk');
});

// Public share links — no auth required
Route::prefix('s')->name('share.')->group(function () {
    Route::get('/{token}', [ShareController::class, 'show'])->name('show');
    Route::post('/{token}/auth', [ShareController::class, 'authenticate'])->name('authenticate');
    Route::get('/{token}/download', [ShareController::class, 'download'])->name('download');
});
