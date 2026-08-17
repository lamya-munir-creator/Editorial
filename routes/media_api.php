<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MediaController;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/media', [MediaController::class, 'index']);
    Route::post('/media', [MediaController::class, 'store']);
    Route::delete('/media/{id}', [MediaController::class, 'destroy']);
});