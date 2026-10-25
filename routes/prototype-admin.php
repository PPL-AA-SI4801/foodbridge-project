<?php

use Illuminate\Support\Facades\Route;

Route::get('/prototype-admin/sidebar', function () {
    $variant = request()->query('variant', 'A');
    $variants = ['A', 'B', 'C'];

    if (! in_array($variant, $variants, true)) {
        $variant = 'A';
    }

    return view('prototype-admin.sidebar', [
        'variant' => $variant,
        'variants' => $variants,
    ]);
})->name('prototype-admin.sidebar');
