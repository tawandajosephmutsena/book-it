<?php

namespace App\Http\Controllers;

use App\Models\GoogleIntegration;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    /**
     * Redirect the user to the Google authentication page.
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')
            ->scopes(['https://www.googleapis.com/auth/calendar', 'https://www.googleapis.com/auth/calendar.events'])
            ->with(['access_type' => 'offline', 'prompt' => 'consent select_account'])
            ->redirect();
    }

    /**
     * Obtain the user information from Google.
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            $user = Auth::user();

            if (! $user) {
                return redirect('/login')->with('error', 'You must be logged in to connect a Google account.');
            }

            $existing = GoogleIntegration::where('user_id', $user->id)->first();
            $refreshToken = !empty($googleUser->refreshToken) ? $googleUser->refreshToken : ($existing->refresh_token ?? '');

            // Create or update the integration
            GoogleIntegration::updateOrCreate(
                [
                    'user_id' => $user->id,
                ],
                [
                    'google_id' => $googleUser->getId(),
                    'email' => $googleUser->getEmail(),
                    'access_token' => $googleUser->token,
                    'refresh_token' => $refreshToken,
                    'expires_in' => $googleUser->expiresIn ?? 3600,
                ]
            );

            $team = $user->currentTeam ?? $user->personalTeam();
            $redirectUrl = $team ? "/{$team->slug}/dashboard" : '/dashboard';

            return redirect($redirectUrl)->with('status', 'Google Calendar connected successfully!');

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Google OAuth callback error: '.$e->getMessage());
            return redirect('/dashboard')->with('error', 'Failed to connect Google account. Please try again.');
        }
    }
}
