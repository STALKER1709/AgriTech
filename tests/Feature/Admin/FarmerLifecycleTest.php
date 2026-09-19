<?php

declare(strict_types=1);

use App\Enums\PaymentMethod;
use App\Enums\UserStatus;
use App\Livewire\Account\Status as AccountStatus;
use App\Livewire\Admin\FarmerValidation;
use App\Livewire\Auth\RegisterFarmer;
use App\Livewire\Payments\Sandbox;
use App\Models\Payment;
use App\Models\Privilege;
use App\Models\User;
use App\Notifications\FarmerApproved;
use App\Notifications\FarmerRejected;
use Database\Seeders\PrivilegeSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

/**
 * Sign-up, fee, decision: the whole farmer path, phases 2 to 4 end to end.
 */
beforeEach(function () {
    $this->seed(PrivilegeSeeder::class);
    $this->seed(SettingSeeder::class);

    $this->admin = User::factory()->admin()->create();
    $this->admin->privileges()->attach(
        Privilege::query()->where('code', Privilege::APPROVE_FARMERS)->value('id'),
    );
});

function signUpAndPay(): User
{
    Livewire::test(RegisterFarmer::class)
        ->set('first_name', 'Bernard')
        ->set('last_name', 'Awono')
        ->set('email', 'bernard@agritech.local')
        ->set('phone', '650 00 00 77')
        ->set('password', 'mot-de-passe-solide')
        ->set('password_confirmation', 'mot-de-passe-solide')
        ->set('farm_name', 'Ferme du Mbam')
        ->set('region', 'Centre')
        ->set('city', 'Obala')
        ->call('register');

    $farmer = User::query()->where('email', 'bernard@agritech.local')->sole();

    test()->actingAs($farmer);

    Livewire::test(AccountStatus::class)
        ->set('method', PaymentMethod::MtnMomo->value)
        ->set('phone', '650 00 00 77')
        ->call('payRegistrationFee');

    Livewire::test(Sandbox::class, ['payment' => Payment::query()->sole()])
        ->set('phone', '650 00 00 77')
        ->call('confirm');

    return $farmer->refresh();
}

it('carries a farmer from sign-up to an open workspace', function () {
    Notification::fake();

    $farmer = signUpAndPay();

    expect($farmer->status)->toBe(UserStatus::PendingValidation);

    // Still shut out of the farmer area while the decision is pending.
    $this->actingAs($farmer)
        ->get(route('farmer.dashboard'))
        ->assertRedirect(route('account.status'));

    Livewire::actingAs($this->admin)
        ->test(FarmerValidation::class)
        ->call('approve', $farmer->id);

    expect($farmer->refresh()->status)->toBe(UserStatus::Active);
    expect($farmer->canPublish())->toBeTrue();

    // And now the area opens.
    $this->actingAs($farmer)
        ->get(route('farmer.dashboard'))
        ->assertOk();

    Notification::assertSentTo($farmer, FarmerApproved::class);
});

it('leaves a refused farmer outside, with the reason on their screen', function () {
    Notification::fake();

    $farmer = signUpAndPay();

    Livewire::actingAs($this->admin)
        ->test(FarmerValidation::class)
        ->call('startRejection', $farmer->id)
        ->set('reason', 'Le nom de l\'exploitation ne correspond pas aux justificatifs.')
        ->call('reject')
        ->assertHasNoErrors();

    expect($farmer->refresh()->status)->toBe(UserStatus::Rejected);

    Notification::assertSentTo(
        $farmer,
        FarmerRejected::class,
        fn (FarmerRejected $notification): bool => str_contains($notification->reason, 'justificatifs'),
    );

    // A refused account cannot sign in at all any more. The session is still
    // the farmer's at this point, and the login route is for guests only.
    Auth::logout();

    $this->post(route('login.store'), [
        'login' => 'bernard@agritech.local',
        'password' => 'mot-de-passe-solide',
    ])->assertSessionHasErrors(['login' => __('auth.rejected')]);
});

it('shows the waiting farmer on the validation screen, and drops them once decided', function () {
    Notification::fake();

    $farmer = signUpAndPay();

    Livewire::actingAs($this->admin)
        ->test(FarmerValidation::class)
        ->assertSee('Ferme du Mbam')
        ->call('approve', $farmer->id)
        ->assertDontSee('Ferme du Mbam');
});
