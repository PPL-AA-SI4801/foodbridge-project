<?php

use Illuminate\Support\Facades\Route;

Route::get('/prototype/sidebar', function () {
    $variant = request()->query('variant', 'A');
    $variants = ['A', 'B', 'C'];

    if (! in_array($variant, $variants, true)) {
        $variant = 'A';
    }

    return view('prototype.sidebar', [
        'variant' => $variant,
        'variants' => $variants,
    ]);
})->name('prototype.sidebar');
