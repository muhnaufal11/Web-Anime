<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Image Routes
|--------------------------------------------------------------------------
|
| These routes serve optimized images without session middleware.
|
*/

Route::get('/img/{size}/{path}', [\App\Http\Controllers\ImageController::class, 'thumbnail'])
    ->where('path', '.*')
    ->name('image.thumbnail');
