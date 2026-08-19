<?php

use App\Models\Booking;
use App\Models\GoogleIntegration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('selected booking shows detailed calendar sync warning when meet link is missing', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    GoogleIntegration::factory()->create([
        'user_id' => $user->id,
        'email' => 'calendar-owner@example.com',
    ]);

    $booking = Booking::factory()->forTeam($team)->create([
        'user_id' => $user->id,
        'meet_link' => null,
        'guest_email' => 'guest@example.com',
        'status' => 'confirmed',
    ]);

    $this->actingAs($user);

    Livewire::test('dashboard-manager')
        ->call('selectBooking', $booking->id)
        ->assertSee('Calendar Sync Needs Attention')
        ->assertSee('This booking is confirmed, but no Google Meet link was saved yet.')
        ->assertSee('calendar-owner@example.com')
        ->assertSee('Google Calendar API access');
});
