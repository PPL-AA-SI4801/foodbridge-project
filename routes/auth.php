<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerificationStatusController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('verifikasi/status', [VerificationStatusController::class, 'status'])
        ->middleware('throttle:60,1')
        ->name('verifikasi.status');

    Route::get('verifikasi/masuk', [VerificationStatusController::class, 'enter'])
        ->middleware('throttle:30,1')
        ->name('verifikasi.masuk');

    Route::post('verifikasi/dokumen', [VerificationStatusController::class, 'store'])
        ->name('verifikasi.dokumen.store');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
