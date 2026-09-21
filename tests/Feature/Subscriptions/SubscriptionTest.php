<?php

declare(strict_types=1);

use App\Enums\PaymentMethod;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Livewire\Client\Subscriptions;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Training;
use App\Models\User;
use App\Services\Subscriptions\ExpireSubscriptions;
use App\Services\Subscriptions\SubscriptionService;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

/**
 * Phase 8: the subscription lifecycle, and the access it opens.
 */
beforeEach(function () {
    $this->client = User::factory()->client()->create();
    $this->plan = SubscriptionPlan::factory()->create([
        'price' => 5000,
        'duration_days' => 30,
    ]);
});

describe('subscribing', function () {
    it('starts a payment whose amount is the plan price, never the form\'s', function () {
        $service = app(SubscriptionService::class);

        $service->start($this->plan, $this->client, PaymentMethod::MtnMomo, PhoneNumber::parse('+237650000001'));

        $payment = Payment::query()->sole();

        expect($payment->amount->amount)->toBe(5000);
        expect($payment->purpose)->toBe(PaymentPurpose::Subscription);
        expect($payment->status)->toBe(PaymentStatus::Initiated);

        $subscription = Subscription::query()->sole();
        expect($subscription->status)->toBe(SubscriptionStatus::PendingPayment);
    });

    it('opens the term only when the webhook confirms the payment', function () {
        $service = app(SubscriptionService::class);

        $service->start($this->plan, $this->client, PaymentMethod::MtnMomo, PhoneNumber::parse('+237650000001'));
        $payment = Payment::query()->sole();

        // Before the callback: nothing is granted.
        expect($this->client->hasActiveSubscription())->toBeFalse();

        deliverOrderCallback($payment, PaymentStatus::Succeeded)->assertOk();

        $subscription = Subscription::query()->sole();

        expect($subscription->refresh()->status)->toBe(SubscriptionStatus::Active);
        expect($subscription->starts_at)->not->toBeNull();
        expect($subscription->ends_at->equalTo($subscription->starts_at->copy()->addDays(30)))->toBeTrue();
        expect($this->client->hasActiveSubscription())->toBeTrue();
    });

    it('sends a second click back to the payment already in flight', function () {
        $service = app(SubscriptionService::class);

        $first = $service->start($this->plan, $this->client, PaymentMethod::MtnMomo, PhoneNumber::parse('+237650000001'));
        $second = $service->start($this->plan, $this->client, PaymentMethod::MtnMomo, PhoneNumber::parse('+237650000001'));

        expect($second->providerReference)->toBe($first->providerReference);
        expect(Payment::query()->count())->toBe(1);
    });

    it('refuses a second subscription while a term is still running', function () {
        $service = app(SubscriptionService::class);

        $service->start($this->plan, $this->client, PaymentMethod::MtnMomo, PhoneNumber::parse('+237650000001'));
        deliverOrderCallback(Payment::query()->sole(), PaymentStatus::Succeeded)->assertOk();

        $otherPlan = SubscriptionPlan::factory()->create(['price' => 9000, 'duration_days' => 90]);

        $service->start($otherPlan, $this->client, PaymentMethod::MtnMomo, PhoneNumber::parse('+237650000001'));
    })->throws(DomainException::class);

    it('lets the client subscribe again once the term has lapsed', function () {
        $service = app(SubscriptionService::class);

        $service->start($this->plan, $this->client, PaymentMethod::MtnMomo, PhoneNumber::parse('+237650000001'));
        deliverOrderCallback(Payment::query()->sole(), PaymentStatus::Succeeded)->assertOk();

        Subscription::query()->sole()->forceFill(['ends_at' => now()->subDay()])->save();

        $service->start($this->plan, $this->client, PaymentMethod::MtnMomo, PhoneNumber::parse('+237650000001'));

        expect(Subscription::query()->count())->toBe(2);
    });

    it('records a refusal and leaves the subscription unpaid', function () {
        $service = app(SubscriptionService::class);

        $service->start($this->plan, $this->client, PaymentMethod::MtnMomo, PhoneNumber::parse('+237650000001'));
        deliverOrderCallback(Payment::query()->sole(), PaymentStatus::Failed)->assertOk();

        expect(Subscription::query()->sole()->refresh()->status)->toBe(SubscriptionStatus::PendingPayment);
        expect($this->client->hasActiveSubscription())->toBeFalse();
    });

    it('refuses to subscribe to a plan that is no longer offered', function () {
        $this->plan->update(['is_active' => false]);

        app(SubscriptionService::class)->start($this->plan, $this->client, PaymentMethod::MtnMomo, PhoneNumber::parse('+237650000001'));
    })->throws(DomainException::class);
});

describe('expiration', function () {
    it('closes the subscriptions whose term has passed', function () {
        Subscription::factory()->forClient($this->client)->active()->create([
            'ends_at' => now()->subDay(),
        ]);

        $count = app(ExpireSubscriptions::class)->expire();

        expect($count)->toBe(1);
        expect(Subscription::query()->sole()->refresh()->status)->toBe(SubscriptionStatus::Expired);
        expect($this->client->hasActiveSubscription())->toBeFalse();
    });

    it('leaves a running subscription alone', function () {
        Subscription::factory()->forClient($this->client)->active()->create([
            'ends_at' => now()->addDays(10),
        ]);

        $count = app(ExpireSubscriptions::class)->expire();

        expect($count)->toBe(0);
        expect(Subscription::query()->sole()->refresh()->status)->toBe(SubscriptionStatus::Active);
    });

    it('lets a lapsed subscription renew', function () {
        $service = app(SubscriptionService::class);

        Subscription::factory()->forClient($this->client)->lapsed()->create(['plan_id' => $this->plan->id]);

        // The expired term no longer blocks a new payment.
        $service->start($this->plan, $this->client, PaymentMethod::MtnMomo, PhoneNumber::parse('+237650000001'));

        deliverOrderCallback(Payment::query()->sole(), PaymentStatus::Succeeded)->assertOk();

        $renewal = Subscription::query()->latest('id')->first();

        expect($renewal->status)->toBe(SubscriptionStatus::Active);
    });
});

describe('RG05 through the subscription', function () {
    it('opens an included training for the duration of the term', function () {
        $training = Training::factory()->published()->includedInSubscription()->create();

        Subscription::factory()->forClient($this->client)->active()->create(['plan_id' => $this->plan->id]);

        expect($training->isAccessibleBy($this->client))->toBeTrue();
    });

    it('closes the included training when the term expires', function () {
        $training = Training::factory()->published()->includedInSubscription()->create();

        Subscription::factory()->forClient($this->client)->active()->create([
            'plan_id' => $this->plan->id,
            'ends_at' => now()->subHour(),
        ]);

        expect($training->isAccessibleBy($this->client))->toBeFalse();
    });

    it('never opens a training that is not included, whatever the subscription', function () {
        $training = Training::factory()->published()->create(['included_in_subscription' => false]);

        Subscription::factory()->forClient($this->client)->active()->create(['plan_id' => $this->plan->id]);

        expect($training->isAccessibleBy($this->client))->toBeFalse();
    });
});

describe('the subscription screen', function () {
    it('shows the running plan and refuses a second payment from the screen', function () {
        $service = app(SubscriptionService::class);
        $service->start($this->plan, $this->client, PaymentMethod::MtnMomo, PhoneNumber::parse('+237650000001'));
        deliverOrderCallback(Payment::query()->sole(), PaymentStatus::Succeeded)->assertOk();

        Livewire::actingAs($this->client)
            ->test(Subscriptions::class)
            ->assertSee($this->plan->name)
            ->assertDontSee('data-test="subscribe-form"');
    });

    it('walks a client through choosing a plan and paying it', function () {
        Notification::fake();

        Livewire::actingAs($this->client)
            ->test(Subscriptions::class)
            ->assertDontSee('data-test="current-subscription"')
            ->call('selectPlan', $this->plan->id)
            ->set('phone', '+237 650 00 00 01')
            ->call('subscribe')
            ->assertHasNoErrors();

        expect(Payment::query()->where('purpose', PaymentPurpose::Subscription)->count())->toBe(1);
        expect(Subscription::query()->where('status', SubscriptionStatus::PendingPayment)->count())->toBe(1);
    });

    it('refuses the screen to a farmer', function () {
        $farmer = sellingFarmer();

        Livewire::actingAs($farmer)->test(Subscriptions::class)->assertForbidden();
    });
});
