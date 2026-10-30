<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\VerificationController;
use App\Http\Controllers\Admin\VerificationDocumentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route(auth()->user()->dashboardRouteName());
    }

    return redirect()->route('login');
})->name('welcome');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard/donor', [DashboardController::class, 'donor'])
        ->name('dashboard.donor')
        ->middleware('role:donor');

    Route::get('/dashboard/penerima', [DashboardController::class, 'recipient'])
        ->name('dashboard.recipient')
        ->middleware('role:penerima');

    Route::middleware('role:donor,penerima')->group(function () {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    });

    Route::middleware('role:administrator')->group(function () {
        Route::get('/dashboard/admin', [AdminDashboardController::class, 'index'])
            ->name('dashboard.admin');

        Route::get('/admin/verifications', [VerificationController::class, 'index'])->name('admin.verifications.index');
        Route::get('/admin/verifications/{user}', [VerificationController::class, 'show'])->name('admin.verifications.show');
        Route::post('/admin/verifications/{user}/approve', [VerificationController::class, 'approve'])->name('admin.verifications.approve');
        Route::post('/admin/verifications/{user}/reject', [VerificationController::class, 'reject'])->name('admin.verifications.reject');
        Route::get('/admin/documents/{verificationDocument}', [VerificationDocumentController::class, 'show'])->name('admin.documents.show');
    });
});

require __DIR__.'/auth.php';
