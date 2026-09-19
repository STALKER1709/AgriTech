<?php

declare(strict_types=1);

use App\Enums\UserStatus;
use App\Models\User;

it('signs in with an email address', function () {
    $user = User::factory()->create(['email' => 'clarisse@agritech.local']);

    $this->post(route('login.store'), [
        'login' => 'clarisse@agritech.local',
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
});

it('signs in with an email address whatever its case', function () {
    $user = User::factory()->create(['email' => 'clarisse@agritech.local']);

    $this->post(route('login.store'), [
        'login' => 'Clarisse@AgriTech.Local',
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);
});

it('signs in with a phone number, however it is written', function (string $typed) {
    $user = User::factory()->create(['phone' => '+237650000001']);

    $this->post(route('login.store'), [
        'login' => $typed,
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
})->with([
    'bare national' => ['650000001'],
    'spaced' => ['650 00 00 01'],
    'international' => ['+237650000001'],
    'international spaced' => ['+237 650 00 00 01'],
    'international prefix' => ['00237650000001'],
]);

it('refuses a wrong password', function () {
    User::factory()->create(['email' => 'clarisse@agritech.local']);

    $this->post(route('login.store'), [
        'login' => 'clarisse@agritech.local',
        'password' => 'mauvais-mot-de-passe',
    ])->assertSessionHasErrors('login');

    $this->assertGuest();
});

it('refuses an unknown identifier', function (string $login) {
    $this->post(route('login.store'), [
        'login' => $login,
        'password' => 'password',
    ])->assertSessionHasErrors('login');

    $this->assertGuest();
})->with([
    'unknown email' => ['inconnu@agritech.local'],
    'unknown phone' => ['690999999'],
    'not an identifier at all' => ['bonjour'],
]);

describe('accounts that may not sign in', function () {
    it('refuses a suspended account and says so', function () {
        $user = User::factory()->farmer()->suspended()->create(['email' => 'suspendu@agritech.local']);

        $response = $this->post(route('login.store'), [
            'login' => $user->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors(['login' => __('auth.suspended')]);
        $this->assertGuest();
    });

    it('refuses a rejected account', function () {
        $user = User::factory()->rejected()->create();

        $this->post(route('login.store'), [
            'login' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors(['login' => __('auth.rejected')]);

        $this->assertGuest();
    });

    it('refuses a deleted account', function () {
        $user = User::factory()->create();
        $user->transitionTo(UserStatus::Deleted);

        $this->post(route('login.store'), [
            'login' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors(['login' => __('auth.deleted')]);

        $this->assertGuest();
    });

    it('lets a farmer awaiting payment or validation sign in', function (string $state) {
        $farmer = User::factory()->{$state}()->create();

        $this->post(route('login.store'), [
            'login' => $farmer->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($farmer);
    })->with(['awaitingPayment', 'awaitingValidation']);
});

it('throttles repeated failures', function () {
    $user = User::factory()->create(['email' => 'cible@agritech.local']);

    foreach (range(1, 5) as $ignored) {
        $this->post(route('login.store'), [
            'login' => $user->email,
            'password' => 'mauvais',
        ]);
    }

    $response = $this->post(route('login.store'), [
        'login' => $user->email,
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors('login');
    $this->assertGuest();
});
