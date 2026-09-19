<?php

declare(strict_types=1);

use App\Enums\UserStatus;
use App\Exceptions\InvalidStatusTransition;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Privilege;
use App\Models\User;
use App\Services\Admin\UserModerationService;
use Database\Seeders\PrivilegeSeeder;

/**
 * Business rule RG08: deleting a user removes the person and keeps the row,
 * so that the orders and payments pointing at it stay readable.
 */
beforeEach(function () {
    $this->seed(PrivilegeSeeder::class);

    $this->admin = User::factory()->admin()->create();
    $this->admin->privileges()->attach(
        Privilege::query()->where('code', Privilege::DELETE_USERS)->value('id'),
    );
});

it('removes the personal data and keeps the row', function () {
    $client = User::factory()->client()->create([
        'first_name' => 'Clarisse',
        'last_name' => 'Etoundi',
        'email' => 'clarisse@agritech.local',
        'phone' => '+237650000123',
    ]);
    $id = $client->id;

    app(UserModerationService::class)->delete($client, $this->admin);

    $deleted = User::query()->findOrFail($id);

    expect($deleted->status)->toBe(UserStatus::Deleted);
    expect($deleted->email)->toBeNull();
    expect($deleted->phone)->toBeNull();
    expect($deleted->first_name)->toBe('Compte');
    expect($deleted->last_name)->toBe('supprimé');
    expect($deleted->email_verified_at)->toBeNull();
});

it('keeps the orders and payments readable', function () {
    $client = User::factory()->client()->create();
    $order = Order::factory()->forClient($client)->paid()->create();
    $payment = Payment::factory()->succeeded()->create(['user_id' => $client->id]);

    app(UserModerationService::class)->delete($client, $this->admin);

    expect(Order::query()->find($order->id))->not->toBeNull();
    expect(Payment::query()->find($payment->id))->not->toBeNull();
    expect($order->refresh()->client_id)->toBe($client->id);
    expect($payment->refresh()->user_id)->toBe($client->id);
});

it('makes the account impossible to sign in to', function () {
    $client = User::factory()->client()->create(['email' => 'aurevoir@agritech.local']);

    app(UserModerationService::class)->delete($client, $this->admin);

    $this->post(route('login.store'), [
        'login' => 'aurevoir@agritech.local',
        'password' => 'password',
    ])->assertSessionHasErrors('login');

    $this->assertGuest();
});

it('frees the email and phone for a new account', function () {
    $client = User::factory()->client()->create([
        'email' => 'reprise@agritech.local',
        'phone' => '+237650000321',
    ]);

    app(UserModerationService::class)->delete($client, $this->admin);

    // Nulled rather than kept as a placeholder, so nothing blocks a genuine
    // new sign-up on the same contact details.
    $newcomer = User::factory()->client()->create([
        'email' => 'reprise@agritech.local',
        'phone' => '+237650000321',
    ]);

    expect($newcomer->exists)->toBeTrue();
});

it('lets several accounts be deleted, despite the unique columns', function () {
    $first = User::factory()->client()->create();
    $second = User::factory()->client()->create();

    app(UserModerationService::class)->delete($first, $this->admin);
    app(UserModerationService::class)->delete($second, $this->admin);

    expect(User::query()->where('status', UserStatus::Deleted)->count())->toBe(2);
});

it('strips an administrator of their privileges', function () {
    $target = User::factory()->admin()->create();
    $target->privileges()->attach(Privilege::query()->pluck('id'));
    User::factory()->admin()->create();

    app(UserModerationService::class)->delete($target, $this->admin);

    expect($target->load('privileges')->privileges)->toBeEmpty();
});

it('never brings a deleted account back', function () {
    $client = User::factory()->client()->create();

    app(UserModerationService::class)->delete($client, $this->admin);

    expect(fn () => $client->reinstate())
        ->toThrow(InvalidStatusTransition::class);
});
