<?php

use App\Models\Booking;
use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

test('selectDate filters out already-booked time slots', function () {
    // Create a team with an owner and availability
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $team->members()->attach($user, ['role' => 'owner']);

    // Monday availability 9:00-17:00
    $team->availabilities()->create([
        'type' => 'recurring',
        'day_of_week' => 'Monday',
        'start_time' => '09:00:00',
        'end_time' => '17:00:00',
        'is_available' => true,
    ]);

    // Find the next Monday for booking
    $nextMonday = now()->next('Monday')->format('Y-m-d');

    // Create an existing confirmed booking at 10:00 on that Monday
    Booking::factory()
        ->forTeam($team)
        ->at($nextMonday, '10:00')
        ->create([
            'user_id' => $user->id,
        ]);

    // Mount the booking wizard
    $component = Livewire::test('booking-wizard', ['team' => $team]);

    // Verify all default times are available before date selection
    $defaultTimes = $component->get('availableTimes');
    expect($defaultTimes)->toBe(['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00']);

    // Select the date with the existing booking (simulates calendar click UI flow)
    $component->call('selectDate', $nextMonday);

    // Available times are now structured: [['time' => 'H:i', 'available' => bool], ...]
    $availableTimes = $component->get('availableTimes');

    // The 10:00 slot should appear but be marked as unavailable (grayed out)
    $tenAmSlot = collect($availableTimes)->firstWhere('time', '10:00');
    expect($tenAmSlot)->not->toBeNull();
    expect($tenAmSlot['available'])->toBeFalse();

    // Other slots should be available
    $nineAmSlot = collect($availableTimes)->firstWhere('time', '09:00');
    expect($nineAmSlot['available'])->toBeTrue();

    $elevenAmSlot = collect($availableTimes)->firstWhere('time', '11:00');
    expect($elevenAmSlot['available'])->toBeTrue();
});

test('cancelled bookings do not block time slots', function () {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $team->members()->attach($user, ['role' => 'owner']);

    $team->availabilities()->create([
        'type' => 'recurring',
        'day_of_week' => 'Monday',
        'start_time' => '09:00:00',
        'end_time' => '17:00:00',
        'is_available' => true,
    ]);

    $nextMonday = now()->next('Monday')->format('Y-m-d');

    // Create a cancelled booking at 10:00
    Booking::factory()
        ->forTeam($team)
        ->at($nextMonday, '10:00')
        ->cancelled()
        ->create([
            'user_id' => $user->id,
        ]);

    $component = Livewire::test('booking-wizard', ['team' => $team]);
    $component->call('selectDate', $nextMonday);

    // Cancelled bookings should NOT block slots — 10:00 should be available
    $availableTimes = $component->get('availableTimes');
    $tenAmSlot = collect($availableTimes)->firstWhere('time', '10:00');
    expect($tenAmSlot)->not->toBeNull();
    expect($tenAmSlot['available'])->toBeTrue();
});

test('submit prevents double booking at server level', function () {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $team->members()->attach($user, ['role' => 'owner']);

    $team->availabilities()->create([
        'type' => 'recurring',
        'day_of_week' => 'Monday',
        'start_time' => '09:00:00',
        'end_time' => '17:00:00',
        'is_available' => true,
    ]);

    $nextMonday = now()->next('Monday')->format('Y-m-d');

    // Create an existing booking at 10:00
    Booking::factory()
        ->forTeam($team)
        ->at($nextMonday, '10:00')
        ->create([
            'user_id' => $user->id,
        ]);

    // Try to submit a conflicting booking
    $component = Livewire::test('booking-wizard', ['team' => $team])
        ->set('step', 1)
        ->set('date', $nextMonday)
        ->set('time', '10:00')
        ->call('nextStep')
        ->set('guest_name', 'Conflict Booker')
        ->set('guest_email', 'conflict@test.com')
        ->set('guest_timezone', 'UTC');

    $component->call('submit')
        ->assertHasErrors(['time']);
});

test('calendar click UI flow populates available time slots', function () {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $team->members()->attach($user, ['role' => 'owner']);

    // Wednesday availability: only morning slots
    $team->availabilities()->create([
        'type' => 'recurring',
        'day_of_week' => 'Wednesday',
        'start_time' => '09:00:00',
        'end_time' => '12:00:00',
        'is_available' => true,
    ]);

    // Also add afternoon availability on Wednesday
    $team->availabilities()->create([
        'type' => 'recurring',
        'day_of_week' => 'Wednesday',
        'start_time' => '14:00:00',
        'end_time' => '16:00:00',
        'is_available' => true,
    ]);

    $nextWednesday = now()->next('Wednesday')->format('Y-m-d');

    // Book the 10:00 slot
    Booking::factory()
        ->forTeam($team)
        ->at($nextWednesday, '10:00')
        ->create([
            'user_id' => $user->id,
        ]);

    // Mount and simulate clicking a calendar day (UI flow)
    $component = Livewire::test('booking-wizard', ['team' => $team])
        ->call('selectDate', $nextWednesday);

    // Should have set the date
    expect($component->get('date'))->toBe($nextWednesday);

    // Should have structured time slots
    $slots = $component->get('availableTimes');

    // Expected: 09:00, 10:00, 11:00 (morning) + 14:00, 15:00 (afternoon)
    expect($slots)->toHaveCount(5);

    // 10:00 should be grayed out (booked)
    $tenAm = collect($slots)->firstWhere('time', '10:00');
    expect($tenAm['available'])->toBeFalse();

    // All others should be available
    foreach (['09:00', '11:00', '14:00', '15:00'] as $time) {
        $slot = collect($slots)->firstWhere('time', $time);
        expect($slot['available'])->toBeTrue("Expected {$time} to be available");
    }
});
