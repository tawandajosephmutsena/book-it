<?php

use App\Http\Controllers\Api\HermesController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::get('/availability', [HermesController::class, 'availability']);
    Route::post('/book', [HermesController::class, 'book']);
});
