<?php

namespace Tests\Feature;

use App\Models\Team;
use Livewire\Livewire;
use Tests\TestCase;

class LivewireBookingTest extends TestCase
{
    public function test_booking_submission()
    {
        $team = Team::where('slug', 'ottomate-space')->first();

        Livewire::test('booking-wizard', ['team' => $team])
            ->set('step', 1)
            ->set('date', '2026-08-08')
            ->set('time', '09:00')
            ->call('nextStep')
            ->assertSet('step', 2)
            ->set('guest_name', 'Test User')
            ->set('guest_email', 'test@test.com')
            ->set('guest_timezone', 'UTC')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('step', 3);
    }
}
