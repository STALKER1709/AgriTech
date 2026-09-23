<?php

declare(strict_types=1);

use App\Models\User;

/**
 * L'écran qui précède les deux formulaires d'inscription. Il ne crée rien :
 * il oriente, et les deux destinations qu'il propose doivent exister.
 */
it('opens for a visitor', function () {
    $this->get(route('register.choice'))
        ->assertOk()
        ->assertSee(route('register'), escape: false)
        ->assertSee(route('register.farmer'), escape: false);
});

it('is closed to someone already signed in', function () {
    $this->actingAs(User::factory()->client()->create())
        ->get(route('register.choice'))
        ->assertRedirect();
});

it('leads to two forms a visitor can actually open', function () {
    foreach ([route('register'), route('register.farmer')] as $destination) {
        $this->get($destination)->assertOk();
    }
});

it('is the destination the login screen offers', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee(route('register.choice'), escape: false);
});
