<?php

use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route('products.index'));

Route::prefix('products')->name('products.')->group(function () {
    Route::get('/',                  [ProductController::class, 'index'])->name('index');
    Route::post('/{product}/search', [ProductController::class, 'startSearch'])->name('search');
    Route::post('/{product}/accept', [ProductController::class, 'acceptManual'])->name('accept');
    Route::get('/import',            [ProductController::class, 'importForm'])->name('import.form');
    Route::post('/import',           [ProductController::class, 'import'])->name('import');
});
