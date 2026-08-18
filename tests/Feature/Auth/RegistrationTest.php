<?php

test('registration page redirects to login with a closed message', function () {
    $response = $this->get(route('register'));

    $response->assertRedirect(route('login'))
        ->assertSessionHas('status', 'Registration is closed. Please ask your administrator for an invite.');
});

test('the registration submit route is disabled', function () {
    expect(app('router')->has('register.store'))->toBeFalse();
});
