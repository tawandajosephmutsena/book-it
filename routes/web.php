<?php

use App\Http\Controllers\GoogleController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::get('/register', function () {
    return redirect('/login')->with('status', 'Registration is closed. Please ask your administrator for an invite.');
})->name('register');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        $user = auth()->user();
        $team = $user->currentTeam ?? $user->personalTeam() ?? $user->allTeams()->first();
        if (! $team) {
            abort(403, 'No team assigned.');
        }

        return redirect("/{$team->slug}/dashboard");
    });

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
