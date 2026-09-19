<?php

declare(strict_types=1);

use App\Enums\PaymentMethod;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Enums\UserStatus;
use App\Jobs\DeliverSimulatedCallback;
use App\Livewire\Account\Status;
use App\Livewire\Payments\Pending;
use App\Livewire\Payments\Sandbox;
use App\Models\FarmerProfile;
use App\Models\Payment;
use App\Models\User;
use App\Services\Payments\RegistrationFeeService;
use App\Support\PhoneNumber;
use Database\Seeders\SettingSeeder;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(SettingSeeder::class);

    $this->farmer = User::factory()->awaitingPayment()->create(['phone' => '+237650000001']);
    FarmerProfile::factory()->create(['user_id' => $this->farmer->id]);
});

it('walks a farmer from sign-up to awaiting validation', function () {
    User::factory()->admin()->create();

    $this->actingAs($this->farmer);

    // The status screen starts the payment.
    Livewire::test(Status::class)
        ->set('method', PaymentMethod::MtnMomo->value)
        ->set('phone', '650 00 00 01')
        ->call('payRegistrationFee')
        ->assertHasNoErrors();

    $payment = Payment::query()->sole();

    expect($payment->purpose)->toBe(PaymentPurpose::RegistrationFee);
    expect($payment->amount->amount)->toBe(10_000);
    expect($payment->status)->toBe(PaymentStatus::Initiated);

    // The sandbox only queues the operator's answer. Under the synchronous
    // queue the tests run on, that answer is delivered straight away — the
    // separate Queue::fake() test below is what proves the page itself
    // confirms nothing.
    Livewire::test(Sandbox::class, ['payment' => $payment])
        ->set('phone', '650 00 00 01')
        ->call('confirm');

    expect($payment->refresh()->status)->toBe(PaymentStatus::Succeeded);
    expect($this->farmer->refresh()->status)->toBe(UserStatus::PendingValidation);

    // The payment moved through the webhook, not through the page: there is a
    // recorded, verified callback behind it.
    $callback = $payment->callbacks()->sole();
    expect($callback->wasProcessed())->toBeTrue();
    expect($callback->payload['status'])->toBe('succeeded');
});

it('reads the amount from the settings, never from the request', function () {
    $this->actingAs($this->farmer);

    // A tampered form cannot change what is owed.
    Livewire::test(Status::class)
        ->set('phone', '650 00 00 01')
        ->set('method', PaymentMethod::OrangeMoney->value)
        ->call('payRegistrationFee');

    expect(Payment::query()->sole()->amount->amount)->toBe(10_000);
});

it('never confirms a payment on the browser journey alone', function () {
    Queue::fake();

    $this->actingAs($this->farmer);

    Livewire::test(Status::class)
        ->set('phone', '650 00 00 01')
        ->call('payRegistrationFee');

    $payment = Payment::query()->sole();

    Livewire::test(Sandbox::class, ['payment' => $payment])
        ->set('phone', '650 00 00 01')
        ->call('confirm');

    // With the queue held, the callback never runs. Visiting the pending
    // screen as often as you like changes nothing: business rule RG06.
    Livewire::test(Pending::class, ['payment' => $payment])
        ->call('refreshStatus')
        ->call('refreshStatus');

    expect($payment->refresh()->status)->toBe(PaymentStatus::Initiated);
    expect($this->farmer->refresh()->status)->toBe(UserStatus::PendingPayment);

    Queue::assertPushed(DeliverSimulatedCallback::class, 1);
});

it('records a refusal without moving the farmer on', function () {
    $this->actingAs($this->farmer);

    Livewire::test(Status::class)
        ->set('phone', '650 00 00 01')
        ->call('payRegistrationFee');

    $payment = Payment::query()->sole();

    Livewire::test(Sandbox::class, ['payment' => $payment])
        ->set('phone', '650 00 00 01')
        ->call('refuse');

    expect($payment->refresh()->status)->toBe(PaymentStatus::Failed);
    expect($this->farmer->refresh()->status)->toBe(UserStatus::PendingPayment);
});

it('sends no callback at all when the payer walks away', function () {
    Queue::fake();

    $this->actingAs($this->farmer);

    Livewire::test(Status::class)->set('phone', '650 00 00 01')->call('payRegistrationFee');
    $payment = Payment::query()->sole();

    Livewire::test(Sandbox::class, ['payment' => $payment])->call('abandon');

    Queue::assertNothingPushed();
    expect($payment->refresh()->status)->toBe(PaymentStatus::Initiated);
});

it('lets a test number force its own outcome', function (string $number, PaymentStatus $expected) {
    $this->actingAs($this->farmer);

    Livewire::test(Status::class)->set('phone', '650 00 00 01')->call('payRegistrationFee');
    $payment = Payment::query()->sole();

    // Confirm is clicked, but the number overrides the button.
    Livewire::test(Sandbox::class, ['payment' => $payment])
        ->set('phone', $number)
        ->call('confirm');

    expect($payment->refresh()->status)->toBe($expected);
})->with([
    'always fails' => ['670 00 00 00', PaymentStatus::Failed],
    'never settles' => ['670 00 00 99', PaymentStatus::Initiated],
]);

describe('access', function () {
    it('keeps a payment page personal to its payer', function () {
        $this->actingAs($this->farmer);
        Livewire::test(Status::class)->set('phone', '650 00 00 01')->call('payRegistrationFee');
        $payment = Payment::query()->sole();

        $this->actingAs(User::factory()->client()->create());

        $this->get(route('payments.sandbox', ['payment' => $payment->provider_reference]))
            ->assertForbidden();

        $this->get(route('payments.pending', ['payment' => $payment->provider_reference]))
            ->assertForbidden();
    });

    it('sends a visitor to the login page', function () {
        $payment = Payment::factory()->create();

        $this->get(route('payments.sandbox', ['payment' => $payment->provider_reference]))
            ->assertRedirect(route('login'));
    });

    it('refuses to start a fee payment for an account that owes nothing', function () {
        $this->actingAs(User::factory()->client()->create());

        app(RegistrationFeeService::class)->start(
            User::factory()->client()->create(),
            PaymentMethod::MtnMomo,
            PhoneNumber::parse('650000001'),
        );
    })->throws(DomainException::class);
});

it('refuses an invalid phone number on the payment page', function () {
    $this->actingAs($this->farmer);
    Livewire::test(Status::class)->set('phone', '650 00 00 01')->call('payRegistrationFee');
    $payment = Payment::query()->sole();

    Livewire::test(Sandbox::class, ['payment' => $payment])
        ->set('phone', '+33650000001')
        ->call('confirm')
        ->assertHasErrors('phone');

    expect($payment->refresh()->status)->toBe(PaymentStatus::Initiated);
});
