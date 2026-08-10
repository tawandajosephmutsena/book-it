<?php

use App\Http\Controllers\GoogleController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware('auth')->group(function () {
    Route::get('/auth/google', [GoogleController::class, 'redirectToGoogle'])->name('google.connect');
    Route::get('/auth/google/callback', [GoogleController::class, 'handleGoogleCallback']);
});

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::view('dashboard', 'dashboard')->name('dashboard');
        Route::view('docs', 'docs')->name('docs');
    });

// Fallback docs route (no team prefix needed)
Route::view('/docs', 'docs')->name('docs.generic');

require __DIR__.'/settings.php';
