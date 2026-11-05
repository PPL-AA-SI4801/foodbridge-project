<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\VerificationController;
use App\Http\Controllers\Admin\VerificationDocumentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Donor\FoodPostController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Recipient\FoodCatalogController;
use App\Http\Controllers\Recipient\ClaimController as RecipientClaimController;
use App\Http\Controllers\Donor\ClaimController as DonorClaimController;
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

    Route::middleware('role:donor')->prefix('donor')->name('donor.')->group(function () {
        Route::resource('food-posts', FoodPostController::class)
            ->except(['show'])
            ->parameters(['food-posts' => 'foodPost']);
    });

    Route::middleware('role:penerima')->prefix('penerima')->name('recipient.')->group(function () {
        Route::get('/katalog', [FoodCatalogController::class, 'index'])->name('catalog.index');
    });

    Route::middleware('role:donor,penerima')->group(function () {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    });

    Route::get('/notifikasi', [NotificationController::class, 'index'])
        ->name('notifications.index');

    Route::middleware('role:administrator')->group(function () {
        Route::get('/dashboard/admin', [AdminDashboardController::class, 'index'])
            ->name('dashboard.admin');

        Route::get('/admin/verifications', [VerificationController::class, 'index'])->name('admin.verifications.index');
        Route::get('/admin/verifications/{user}', [VerificationController::class, 'show'])->name('admin.verifications.show');
        Route::post('/admin/verifications/{user}/approve', [VerificationController::class, 'approve'])->name('admin.verifications.approve');
        Route::post('/admin/verifications/{user}/reject', [VerificationController::class, 'reject'])->name('admin.verifications.reject');
        Route::get('/admin/documents/{verificationDocument}', [VerificationDocumentController::class, 'show'])->name('admin.documents.show');
    });

        Route::middleware(['auth', 'verified', 'role:penerima'])->prefix('recipient')->name('recipient.')->group(function () {
        Route::get('/claims', [RecipientClaimController::class, 'index'])->name('claims.index');
        Route::post('/food-posts/{foodPost}/claims', [RecipientClaimController::class, 'store'])->name('claims.store');
    });

        Route::middleware(['auth', 'verified', 'role:donor'])->prefix('donor')->name('donor.')->group(function () {
        Route::get('/claims', [DonorClaimController::class, 'index'])->name('claims.index');
        Route::patch('/claims/{claim}/approve', [DonorClaimController::class, 'approve'])->name('claims.approve');
        Route::patch('/claims/{claim}/reject', [DonorClaimController::class, 'reject'])->name('claims.reject');
    });
});

require __DIR__.'/auth.php';
