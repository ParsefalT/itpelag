<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/docs/api-docs.json', function () {
    $path = storage_path('api-docs/api-docs.json');

    abort_unless(is_file($path), 404, 'Swagger spec not found. Run: php artisan l5-swagger:generate');

    return response()->file($path, [
        'Content-Type' => 'application/json',
    ]);
});

Route::prefix('/transactions')->group(function () {
    Route::get('/create', function () {
        return 'test';
    });
});
