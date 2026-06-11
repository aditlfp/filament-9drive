<?php

use App\Http\Controllers\FilePreviewController;
use App\Http\Controllers\GoogleDriveConnectionController;
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
});
