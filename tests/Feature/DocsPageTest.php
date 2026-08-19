<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated team members can view the detailed operations docs page', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $response = $this
        ->actingAs($user)
        ->get(route('docs', ['current_team' => $team]));

    $response
        ->assertOk()
        ->assertSeeText('Book-it Operations Playbook')
        ->assertSeeText('Connect Google Calendar & Meet')
        ->assertSeeText('Booking flow lifecycle')
        ->assertSeeText('Troubleshooting & Recovery');
});
