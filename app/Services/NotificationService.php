<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * Trigger all notifications (Email & WhatsApp) for a confirmed booking.
     */
    public static function trigger(Booking $booking)
    {
        // 1. Create real Google Calendar Event (which generates a real Meet link)
        $calendarResult = GoogleCalendarService::createEvent($booking);
        $booking->refresh(); // Refresh to get the generated meet_link

        if (! $calendarResult['success']) {
            Log::warning("Calendar sync failed for booking {$booking->id}: {$calendarResult['error']}");
        }

        // 2. Send Email Notification
        self::sendEmail($booking);

        // 3. Send WhatsApp Notification
        self::sendWhatsApp($booking);
    }

    /**
     * Send email confirmation using customized template.
     */
    protected static function sendEmail(Booking $booking)
    {
        $settingsFile = storage_path('app/notification_settings.json');
        $settings = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) : [];

        $subjectTemplate = $settings['email_subject'] ?? 'Booking Confirmed: {team_name} Session';
        $bodyTemplate = $settings['email_body'] ?? "Hi {guest_name},\n\nYour meeting with {team_name} is confirmed for {start_time}.\n\nGoogle Meet: {meet_link}\n\nWe look forward to meeting you!";

        // Replace placeholders
        $replacements = [
            '{guest_name}' => $booking->guest_name,
            '{team_name}' => $booking->team->name,
            '{start_time}' => $booking->start_time->setTimezone($booking->guest_timezone)->format('F j, Y, g:i A').' ('.$booking->guest_timezone.')',
            '{meet_link}' => $booking->meet_link,
            '{notes}' => $booking->lead_data['notes'] ?? 'None',
        ];

        $subject = str_replace(array_keys($replacements), array_values($replacements), $subjectTemplate);
        $body = str_replace(array_keys($replacements), array_values($replacements), $bodyTemplate);

        try {
            Mail::raw($body, function ($message) use ($booking, $subject) {
                $message->to($booking->guest_email)
                    ->subject($subject);
            });
            Log::info("Booking email sent successfully to: {$booking->guest_email}");
        } catch (\Exception $e) {
            Log::error('Failed to send booking email: '.$e->getMessage());
        }
    }

    /**
     * Send WhatsApp confirmation using Meta API.
     */
    protected static function sendWhatsApp(Booking $booking)
    {
        $settingsFile = storage_path('app/notification_settings.json');
        $settings = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) : [];

        $accessToken = $settings['whatsapp_access_token'] ?? env('WHATSAPP_ACCESS_TOKEN', '');
        $phoneNumberId = $settings['whatsapp_phone_number_id'] ?? env('WHATSAPP_PHONE_NUMBER_ID', '');
        $templateName = $settings['whatsapp_template_name'] ?? 'booking_confirmation';

        if (empty($accessToken) || empty($phoneNumberId)) {
            Log::warning('WhatsApp notifications not triggered: Credentials missing.');

            return;
        }

        // Meta WhatsApp API requires recipient phone with country code (numbers only)
        $recipientPhone = preg_replace('/[^0-9]/', '', $booking->phone ?? '');

        if (empty($recipientPhone)) {
            Log::info("WhatsApp skipped: Guest {$booking->guest_name} did not provide a valid phone number.");

            return;
        }

        $apiUrl = "https://graph.facebook.com/v19.0/{$phoneNumberId}/messages";
        $formattedTime = $booking->start_time->setTimezone($booking->guest_timezone)->format('F j, g:i A');

        try {
            $response = Http::withToken($accessToken)
                ->post($apiUrl, [
                    'messaging_product' => 'whatsapp',
                    'to' => $recipientPhone,
                    'type' => 'template',
                    'template' => [
                        'name' => $templateName,
                        'language' => ['code' => 'en_US'],
                        'components' => [
                            [
                                'type' => 'body',
                                'parameters' => [
                                    ['type' => 'text', 'text' => $booking->guest_name],
                                    ['type' => 'text', 'text' => $formattedTime],
                                ],
                            ],
                        ],
                    ],
                ]);

            if ($response->failed()) {
                Log::error('WhatsApp notification failed: '.$response->body());
            } else {
                Log::info("WhatsApp notification sent to: {$booking->guest_name} ({$recipientPhone})");
            }
        } catch (\Exception $e) {
            Log::error('WhatsApp notifications API error: '.$e->getMessage());
        }
    }
}
