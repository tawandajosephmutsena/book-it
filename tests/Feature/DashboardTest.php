<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard and see plain english labels', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard'));

    $response->assertOk()
        ->assertSee('Manage bookings, availability, and settings')
        ->assertSee('Appointments')
        ->assertSee('Clients')
        ->assertSee('Availability')
        ->assertSee('Public Page')
        ->assertSee('Notification Templates')
        ->assertSee('Integrations');
});
