<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\FoodPostController as AdminFoodPostController;
use App\Http\Controllers\Admin\RequestBoardController as AdminRequestBoardController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\VerificationController;
use App\Http\Controllers\Admin\VerificationDocumentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Donor\ClaimController as DonorClaimController;
use App\Http\Controllers\Donor\FoodPostController;
use App\Http\Controllers\Donor\RequestBoardController as DonorRequestBoardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Recipient\ClaimController as RecipientClaimController;
use App\Http\Controllers\Recipient\FoodCatalogController;
use App\Http\Controllers\Recipient\FoodRequestController as RecipientFoodRequestController;
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

    Route::patch('/notifikasi/baca-semua', [NotificationController::class, 'markAllAsRead'])
        ->name('notifications.read-all');

    Route::get('/notifikasi/{notification}', [NotificationController::class, 'open'])
        ->name('notifications.open');

    Route::patch('/notifikasi/{notification}/baca', [NotificationController::class, 'markAsRead'])
        ->name('notifications.read');

    Route::middleware('role:administrator')->group(function () {
        Route::get('/dashboard/admin', [AdminDashboardController::class, 'index'])
            ->name('dashboard.admin');

        Route::get('/admin/users', [AdminUserController::class, 'index'])->name('admin.users.index');

        Route::get('/admin/verifications', [VerificationController::class, 'index'])->name('admin.verifications.index');
        Route::get('/admin/verifications/{user}', [VerificationController::class, 'show'])->name('admin.verifications.show');
        Route::post('/admin/verifications/{user}/approve', [VerificationController::class, 'approve'])->name('admin.verifications.approve');
        Route::post('/admin/verifications/{user}/reject', [VerificationController::class, 'reject'])->name('admin.verifications.reject');
        Route::get('/admin/documents/{verificationDocument}', [VerificationDocumentController::class, 'show'])->name('admin.documents.show');

        Route::get('/admin/requests', [AdminRequestBoardController::class, 'index'])->name('admin.requests.index');
        Route::patch('/admin/requests/{foodRequest}/cancel', [AdminRequestBoardController::class, 'cancel'])->name('admin.requests.cancel');
        Route::delete('/admin/requests/{foodRequest}', [AdminRequestBoardController::class, 'destroy'])->name('admin.requests.destroy');

        Route::resource('/admin/food-posts', AdminFoodPostController::class)
            ->except(['show'])
            ->names('admin.food-posts')
            ->parameters(['food-posts' => 'foodPost']);
    });

    Route::middleware(['auth', 'verified', 'role:penerima'])->prefix('recipient')->name('recipient.')->group(function () {
        Route::get('/claims', [RecipientClaimController::class, 'index'])->name('claims.index');
        Route::post('/food-posts/{foodPost}/claims', [RecipientClaimController::class, 'store'])->name('claims.store');

        Route::get('/requests', [RecipientFoodRequestController::class, 'index'])->name('requests.index');
        Route::post('/requests', [RecipientFoodRequestController::class, 'store'])->name('requests.store');
        Route::patch('/requests/{foodRequest}/complete', [RecipientFoodRequestController::class, 'complete'])->name('requests.complete');
        Route::patch('/requests/{foodRequest}/cancel', [RecipientFoodRequestController::class, 'cancel'])->name('requests.cancel');
        Route::delete('/requests/{foodRequest}', [RecipientFoodRequestController::class, 'destroy'])->name('requests.destroy');
    });

    Route::middleware(['auth', 'verified', 'role:donor'])->prefix('donor')->name('donor.')->group(function () {
        Route::get('/claims', [DonorClaimController::class, 'index'])->name('claims.index');
        Route::patch('/claims/{claim}/approve', [DonorClaimController::class, 'approve'])->name('claims.approve');
        Route::patch('/claims/{claim}/reject', [DonorClaimController::class, 'reject'])->name('claims.reject');

        Route::get('/requests', [DonorRequestBoardController::class, 'index'])->name('requests.index');
        Route::post('/requests/{foodRequest}/fulfil', [DonorRequestBoardController::class, 'fulfil'])->name('requests.fulfil');
        Route::patch('/requests/{foodRequest}/complete', [DonorRequestBoardController::class, 'complete'])->name('requests.complete');
    });
});

require __DIR__.'/auth.php';
