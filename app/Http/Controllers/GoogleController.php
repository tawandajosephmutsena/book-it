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

            // Create or update the integration
            GoogleIntegration::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'google_id' => $googleUser->getId(),
                ],
                [
                    'email' => $googleUser->getEmail(),
                    'access_token' => $googleUser->token,
                    'refresh_token' => $googleUser->refreshToken ?? '', // Only given on first authorization or with prompt=consent
                    'expires_in' => $googleUser->expiresIn,
                ]
            );

            return redirect('/dashboard')->with('status', 'Google Calendar connected successfully!');

        } catch (\Exception $e) {
            return redirect('/dashboard')->with('error', 'Failed to connect Google account. Please try again.');
        }
    }
}
