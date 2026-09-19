<?php

declare(strict_types=1);

use App\Models\User;

/**
 * The acceptance criterion of this phase: no role reaches another's area, and
 * hiding the link is never what does the work.
 */
dataset('role areas', [
    'client area' => ['client.dashboard', 'client'],
    'farmer area' => ['farmer.dashboard', 'farmer'],
    'admin area' => ['admin.dashboard', 'admin'],
]);

it('sends a visitor to the login page', function (string $route) {
    $this->get(route($route))->assertRedirect(route('login'));
})->with('role areas');

it('lets the owning role in', function (string $route, string $role) {
    $user = User::factory()->{$role}()->create();

    $this->actingAs($user)->get(route($route))->assertOk();
})->with('role areas');

it('refuses every other role with a 403', function (string $route, string $owner) {
    $others = array_diff(['client', 'farmer', 'admin'], [$owner]);

    foreach ($others as $role) {
        $intruder = User::factory()->{$role}()->create();

        $this->actingAs($intruder)
            ->get(route($route))
            ->assertForbidden();
    }
})->with('role areas');

it('keeps a farmer out of their own area until the account is active', function () {
    foreach (['awaitingPayment', 'awaitingValidation'] as $state) {
        $farmer = User::factory()->{$state}()->create();

        $this->actingAs($farmer)
            ->get(route('farmer.dashboard'))
            ->assertRedirect(route('account.status'));
    }
});

it('does not let an inactive farmer slip into another area either', function () {
    $farmer = User::factory()->awaitingPayment()->create();

    $this->actingAs($farmer)->get(route('client.dashboard'))->assertForbidden();
    $this->actingAs($farmer)->get(route('admin.dashboard'))->assertForbidden();
});

it('shows the status screen to anyone signed in', function () {
    $this->actingAs(User::factory()->awaitingPayment()->create())
        ->get(route('account.status'))
        ->assertOk();
});

it('sends an active user away from the status screen', function () {
    $this->actingAs(User::factory()->client()->create())
        ->get(route('account.status'))
        ->assertRedirect(route('dashboard'));
});

it('keeps the farmer sign-up page for visitors only', function () {
    $this->get(route('register.farmer'))->assertOk();

    $this->actingAs(User::factory()->client()->create())
        ->get(route('register.farmer'))
        ->assertRedirect(route('dashboard'));
});
