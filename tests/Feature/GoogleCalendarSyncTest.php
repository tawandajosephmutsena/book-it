<?php

use App\Models\Booking;
use App\Models\GoogleIntegration;
use App\Models\Team;
use App\Models\User;
use App\Services\GoogleCalendarService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->team = Team::factory()->create();
    $this->team->members()->attach($this->user, ['role' => 'owner']);
});

test('calendar sync succeeds with valid Google integration', function () {
    $integration = GoogleIntegration::factory()->create([
        'user_id' => $this->user->id,
    ]);

    $booking = Booking::factory()->forTeam($this->team)->create([
        'user_id' => $this->user->id,
    ]);

    Http::fake([
        'googleapis.com/calendar/*' => Http::response([
            'id' => 'event-123',
            'conferenceData' => [
                'entryPoints' => [
                    ['entryPointType' => 'video', 'uri' => 'https://meet.google.com/abc-defg-hij'],
                ],
            ],
        ], 200),
    ]);

    $result = GoogleCalendarService::createEvent($booking);

    expect($result['success'])->toBeTrue();
    expect($result['error'])->toBeNull();

    $booking->refresh();
    expect($booking->meet_link)->toBe('https://meet.google.com/abc-defg-hij');
});

test('calendar sync returns error when no Google integration exists', function () {
    $booking = Booking::factory()->forTeam($this->team)->create([
        'user_id' => $this->user->id,
    ]);

    Log::shouldReceive('warning')
        ->once()
        ->withArgs(fn ($msg) => str_contains($msg, 'No Google Integration found'));

    $result = GoogleCalendarService::createEvent($booking);

    expect($result['success'])->toBeFalse();
    expect($result['error'])->toContain('No Google Integration found');
});

test('calendar sync returns error when token is expired and no refresh token', function () {
    GoogleIntegration::factory()
        ->expired()
        ->withoutRefreshToken()
        ->create(['user_id' => $this->user->id]);

    $booking = Booking::factory()->forTeam($this->team)->create([
        'user_id' => $this->user->id,
    ]);

    $result = GoogleCalendarService::createEvent($booking);

    expect($result['success'])->toBeFalse();
    expect($result['error'])->toContain('Token may be expired or revoked');
});

test('calendar sync refreshes expired token and retries', function () {
    GoogleIntegration::factory()
        ->expired()
        ->create(['user_id' => $this->user->id]);

    $booking = Booking::factory()->forTeam($this->team)->create([
        'user_id' => $this->user->id,
    ]);

    Http::fake([
        'oauth2.googleapis.com/token' => Http::response([
            'access_token' => 'ya29.new-refreshed-token',
            'expires_in' => 3600,
        ], 200),
        'googleapis.com/calendar/*' => Http::response([
            'id' => 'event-456',
            'conferenceData' => [
                'entryPoints' => [
                    ['entryPointType' => 'video', 'uri' => 'https://meet.google.com/xyz-uvwx-rst'],
                ],
            ],
        ], 200),
    ]);

    $result = GoogleCalendarService::createEvent($booking);

    expect($result['success'])->toBeTrue();

    $booking->refresh();
    expect($booking->meet_link)->toBe('https://meet.google.com/xyz-uvwx-rst');

    // Verify the token was refreshed in the database
    $integration = GoogleIntegration::where('user_id', $this->user->id)->first();
    expect($integration->access_token)->toBe('ya29.new-refreshed-token');
});

test('calendar sync returns error on Google API 401 response', function () {
    GoogleIntegration::factory()->create([
        'user_id' => $this->user->id,
    ]);

    $booking = Booking::factory()->forTeam($this->team)->create([
        'user_id' => $this->user->id,
    ]);

    Http::fake([
        'googleapis.com/calendar/*' => Http::response([
            'error' => ['message' => 'Invalid Credentials', 'code' => 401],
        ], 401),
    ]);

    $result = GoogleCalendarService::createEvent($booking);

    expect($result['success'])->toBeFalse();
    expect($result['error'])->toContain('auth failed (401)');
});

test('calendar sync returns error on Google API generic failure', function () {
    GoogleIntegration::factory()->create([
        'user_id' => $this->user->id,
    ]);

    $booking = Booking::factory()->forTeam($this->team)->create([
        'user_id' => $this->user->id,
    ]);

    Http::fake([
        'googleapis.com/calendar/*' => Http::response([
            'error' => ['message' => 'Rate Limit Exceeded', 'code' => 403],
        ], 403),
    ]);

    $result = GoogleCalendarService::createEvent($booking);

    expect($result['success'])->toBeFalse();
    expect($result['error'])->toContain('Google Calendar API error (403)');
});

test('booking is saved even when calendar sync fails', function () {
    // No Google integration — calendar sync will fail
    $this->team->availabilities()->create([
        'type' => 'recurring',
        'day_of_week' => 'Monday',
        'start_time' => '09:00:00',
        'end_time' => '17:00:00',
        'is_available' => true,
    ]);

    $nextMonday = now()->next('Monday')->format('Y-m-d');

    $component = Livewire\Livewire::test('booking-wizard', ['team' => $this->team])
        ->set('step', 1)
        ->set('date', $nextMonday)
        ->set('time', '09:00')
        ->call('nextStep')
        ->set('guest_name', 'Test User')
        ->set('guest_email', 'test@test.com')
        ->set('guest_timezone', 'UTC')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('step', 3);

    // Booking should exist in the database even though calendar sync failed
    expect(Booking::where('guest_email', 'test@test.com')->exists())->toBeTrue();
});

test('calendar sync handles connection timeout gracefully', function () {
    GoogleIntegration::factory()->create([
        'user_id' => $this->user->id,
    ]);

    $booking = Booking::factory()->forTeam($this->team)->create([
        'user_id' => $this->user->id,
    ]);

    Http::fake([
        'googleapis.com/calendar/*' => fn () => throw new ConnectionException('Connection timed out'),
    ]);

    $result = GoogleCalendarService::createEvent($booking);

    expect($result['success'])->toBeFalse();
    expect($result['error'])->toContain('connection error');
});
