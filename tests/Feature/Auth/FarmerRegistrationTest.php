<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Livewire\Auth\RegisterFarmer;
use App\Models\FarmerProfile;
use App\Models\User;
use Database\Seeders\SettingSeeder;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

/**
 * @return array<string, string>
 */
function farmerPayload(array $overrides = []): array
{
    return array_merge([
        'first_name' => 'Bernard',
        'last_name' => 'Awono',
        'email' => 'bernard@agritech.local',
        'phone' => '650 00 00 42',
        'password' => 'mot-de-passe-solide',
        'password_confirmation' => 'mot-de-passe-solide',
        'farm_name' => 'Ferme du Mbam',
        'region' => 'Centre',
        'city' => 'Obala',
        'description' => 'Plantain et manioc.',
    ], $overrides);
}

function submitFarmerRegistration(array $overrides = []): Testable
{
    $component = Livewire::test(RegisterFarmer::class);

    foreach (farmerPayload($overrides) as $field => $value) {
        $component->set($field, $value);
    }

    return $component->call('register');
}

it('renders the farmer sign-up page', function () {
    $this->get(route('register.farmer'))
        ->assertOk()
        ->assertSee('Créer un compte agriculteur')
        ->assertSee('Votre exploitation');
});

it('creates the account and its farm profile together', function () {
    submitFarmerRegistration()->assertHasNoErrors();

    $farmer = User::query()->where('email', 'bernard@agritech.local')->sole();

    expect($farmer->role)->toBe(UserRole::Farmer);
    expect($farmer->first_name)->toBe('Bernard');
    expect($farmer->phone)->toBe('+237650000042');

    $profile = FarmerProfile::query()->where('user_id', $farmer->id)->sole();

    expect($profile->farm_name)->toBe('Ferme du Mbam');
    expect($profile->region)->toBe('Centre');
    expect($profile->city)->toBe('Obala');
    expect($profile->isValidated())->toBeFalse();
});

it('starts the account awaiting payment, per business rule RG02', function () {
    submitFarmerRegistration()->assertHasNoErrors();

    $farmer = User::query()->where('email', 'bernard@agritech.local')->sole();

    expect($farmer->status)->toBe(UserStatus::PendingPayment);
    expect($farmer->canPublish())->toBeFalse();
});

it('signs the farmer in and sends them to the status screen', function () {
    submitFarmerRegistration()->assertRedirect(route('account.status', absolute: false));

    $this->assertAuthenticated();
});

it('shows the registration fee on the status screen', function () {
    $this->seed(SettingSeeder::class);

    submitFarmerRegistration();

    $this->get(route('account.status'))
        ->assertOk()
        ->assertSee("Frais d'inscription")
        ->assertSee('10'."\u{00A0}".'000'."\u{00A0}".'FCFA')
        // Le montant est lu côté serveur : le bouton le répète, il ne le
        // reçoit pas du formulaire.
        ->assertSee('Payer 10'."\u{00A0}".'000'."\u{00A0}".'FCFA');
});

it('leaves nothing behind when validation fails', function () {
    submitFarmerRegistration(['email' => 'pas-une-adresse'])->assertHasErrors('email');

    expect(User::query()->count())->toBe(0);
    expect(FarmerProfile::query()->count())->toBe(0);
});

it('refuses an email already in use', function () {
    User::factory()->create(['email' => 'bernard@agritech.local']);

    submitFarmerRegistration()->assertHasErrors('email');
});

it('refuses a phone number already in use, whatever its writing', function () {
    User::factory()->create(['phone' => '+237650000042']);

    submitFarmerRegistration(['phone' => '650 00 00 42'])->assertHasErrors('phone');
    submitFarmerRegistration(['phone' => '+237650000042'])->assertHasErrors('phone');
    submitFarmerRegistration(['phone' => '00237650000042'])->assertHasErrors('phone');
});

it('refuses a number that is not Cameroonian', function (string $phone) {
    submitFarmerRegistration(['phone' => $phone])->assertHasErrors('phone');
})->with([
    'wrong country' => ['+33650000001'],
    'too short' => ['65000'],
    'unknown prefix' => ['750000001'],
]);

it('refuses a region outside the ten official ones', function () {
    submitFarmerRegistration(['region' => 'Bretagne'])->assertHasErrors('region');
});

it('requires every field the farm profile needs', function (string $field) {
    submitFarmerRegistration([$field => ''])->assertHasErrors($field);
})->with(['first_name', 'last_name', 'email', 'phone', 'farm_name', 'region', 'city']);

it('requires the password confirmation to match', function () {
    submitFarmerRegistration(['password_confirmation' => 'autre-chose'])->assertHasErrors('password');
});
