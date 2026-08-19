<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\GoogleIntegration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleCalendarService
{
    /**
     * Create an event in the user's Google Calendar and attach a Meet link.
     *
     * @return array{success: bool, error: string|null}
     */
    public static function createEvent(Booking $booking): array
    {
        // 1. Get the Google Integration for the team owner
        $integration = GoogleIntegration::where('user_id', $booking->user_id)->first();

        if (! $integration) {
            $message = "No Google Integration found for user {$booking->user_id}. Skipping calendar sync.";
            Log::warning($message);

            return ['success' => false, 'error' => $message];
        }

        // 2. Ensure we have a valid access token (with retry on refresh failure)
        $accessToken = self::getValidAccessToken($integration);

        if (! $accessToken) {
            $message = "Failed to get valid Google access token for user {$booking->user_id}. Token may be expired or revoked — reconnect Google Calendar.";
            Log::error($message);

            return ['success' => false, 'error' => $message];
        }

        // 3. Prepare Event Data
        $startUTC = $booking->start_time->format('Y-m-d\TH:i:s\Z');
        $endUTC = $booking->end_time->format('Y-m-d\TH:i:s\Z');

        $description = "Strategy Session with {$booking->guest_name}\n";
        $description .= "Email: {$booking->guest_email}\n";

        if (! empty($booking->phone)) {
            $description .= "Phone: {$booking->phone}\n";
        }
        if (! empty($booking->company)) {
            $description .= "Company: {$booking->company}\n";
        }
        if (! empty($booking->industry)) {
            $description .= "Industry: {$booking->industry}\n";
        }
        if (! empty($booking->project_brief)) {
            $description .= "\nBrief:\n{$booking->project_brief}\n";
        }
        if (! empty($booking->notes)) {
            $description .= "\nNotes:\n{$booking->notes}\n";
        }

        $eventData = [
            'summary' => "{$booking->team->name} Strategy Session: {$booking->guest_name}",
            'description' => $description,
            'start' => [
                'dateTime' => $startUTC,
                'timeZone' => 'UTC',
            ],
            'end' => [
                'dateTime' => $endUTC,
                'timeZone' => 'UTC',
            ],
            'attendees' => [
                ['email' => $booking->guest_email],
                ['email' => $integration->email],
            ],
            'conferenceData' => [
                'createRequest' => [
                    'requestId' => $booking->uuid, // Must be unique for each request
                    'conferenceSolutionKey' => [
                        'type' => 'hangoutsMeet',
                    ],
                ],
            ],
        ];

        // 4. Call Google API
        try {
            $response = Http::withToken($accessToken)
                ->timeout(15)
                ->post('https://www.googleapis.com/calendar/v3/calendars/primary/events?conferenceDataVersion=1', $eventData);
        } catch (\Exception $e) {
            $message = "Google Calendar API connection error for booking {$booking->id}: {$e->getMessage()}";
            Log::error($message);

            return ['success' => false, 'error' => $message];
        }

        if ($response->successful()) {
            $event = $response->json();

            // Extract the Meet link if available
            $meetLink = null;
            if (isset($event['conferenceData']['entryPoints'])) {
                foreach ($event['conferenceData']['entryPoints'] as $entryPoint) {
                    if ($entryPoint['entryPointType'] === 'video') {
                        $meetLink = $entryPoint['uri'];
                        break;
                    }
                }
            }

            if ($meetLink) {
                $booking->update(['meet_link' => $meetLink]);
            }

            Log::info("Google Calendar event created for booking {$booking->id}.");

            return ['success' => true, 'error' => null];
        }

        // Handle specific Google API error codes
        $statusCode = $response->status();
        $body = $response->body();

        if ($statusCode === 401) {
            // Token was invalid even after refresh — force a re-auth
            $message = "Google Calendar auth failed (401) for booking {$booking->id}. The access token is invalid — user needs to reconnect Google Calendar.";
            Log::error($message);
            Log::error("Response body: {$body}");

            return ['success' => false, 'error' => $message];
        }

        $message = "Google Calendar API error ({$statusCode}) for booking {$booking->id}: {$body}";
        Log::error($message);

        return ['success' => false, 'error' => $message];
    }

    /**
     * Refresh the access token if needed, with a single retry.
     */
    protected static function getValidAccessToken(GoogleIntegration $integration): ?string
    {
        // Simple check: if updated_at + expires_in is in the past, refresh.
        // We buffer by 5 minutes (300 seconds) to be safe.
        $expiresAt = $integration->updated_at->addSeconds((int) $integration->expires_in - 300);

        if (now()->lessThan($expiresAt)) {
            return $integration->access_token;
        }

        return self::refreshAccessToken($integration);
    }

    /**
     * Refresh the Google OAuth access token.
     */
    protected static function refreshAccessToken(GoogleIntegration $integration): ?string
    {
        if (empty($integration->refresh_token)) {
            Log::error("Google token expired and no refresh token available for user {$integration->user_id}. User must reconnect Google Calendar.");

            return null;
        }

        try {
            $response = Http::timeout(10)
                ->post('https://oauth2.googleapis.com/token', [
                    'client_id' => config('services.google.client_id'),
                    'client_secret' => config('services.google.client_secret'),
                    'refresh_token' => $integration->refresh_token,
                    'grant_type' => 'refresh_token',
                ]);
        } catch (\Exception $e) {
            Log::error("Google token refresh connection error for user {$integration->user_id}: {$e->getMessage()}");

            return null;
        }

        if ($response->successful()) {
            $data = $response->json();

            $integration->update([
                'access_token' => $data['access_token'],
                'expires_in' => $data['expires_in'],
                // Google might not return a new refresh token, so we keep the old one
            ]);

            Log::info("Google access token refreshed for user {$integration->user_id}.");

            return $data['access_token'];
        }

        $body = $response->body();
        Log::error("Failed to refresh Google token for user {$integration->user_id}: {$body}");

        // Check if the refresh token itself is invalid (revoked, expired in testing mode)
        $errorData = $response->json();
        if (isset($errorData['error']) && $errorData['error'] === 'invalid_grant') {
            Log::error("Google refresh token is invalid/revoked for user {$integration->user_id}. User must reconnect Google Calendar from the dashboard.");
        }

        return null;
    }
}
