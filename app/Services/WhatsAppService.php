<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $apiUrl;
    protected string $accessToken;
    protected string $phoneNumberId;

    public function __construct()
    public function __construct()
    {
        $settingsFile = storage_path('app/whatsapp_settings.json');
        $settings = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) : [];

        $this->accessToken = $settings['access_token'] ?? config('services.whatsapp.access_token', env('WHATSAPP_ACCESS_TOKEN', ''));
        $this->phoneNumberId = $settings['phone_number_id'] ?? config('services.whatsapp.phone_number_id', env('WHATSAPP_PHONE_NUMBER_ID', ''));
        $this->apiUrl = "https://graph.facebook.com/v19.0/{$this->phoneNumberId}/messages";
    }

    /**
     * Send a template message for booking confirmation
     */
    public function sendBookingConfirmation(string $to, string $guestName, string $dateTime)
    {
        if (empty($this->accessToken) || empty($this->phoneNumberId)) {
            Log::warning('WhatsApp API credentials not configured. Skipping message.');
            return false;
        }

        $settingsFile = storage_path('app/whatsapp_settings.json');
        $settings = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) : [];
        $templateName = $settings['template_name'] ?? 'booking_confirmation';

        $response = Http::withToken($this->accessToken)
            ->post($this->apiUrl, [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'template',
                'template' => [
                    'name' => $templateName,
                    'language' => ['code' => 'en_US'],
                    'components' => [
                        [
                            'type' => 'body',
                            'parameters' => [
                                ['type' => 'text', 'text' => $guestName],
                                ['type' => 'text', 'text' => $dateTime],
                            ]
                        ]
                    ]
                ]
            ]);

        if ($response->failed()) {
            Log::error('WhatsApp message failed: ' . $response->body());
            return false;
        }

        return true;
    }
}
