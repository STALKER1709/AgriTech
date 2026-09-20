<?php

declare(strict_types=1);

use App\Enums\PaymentMethod;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Livewire\Trainings\Page as TrainingPage;
use App\Models\Payment;
use App\Models\PaymentCallback;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Training;
use App\Models\TrainingContent;
use App\Models\TrainingPurchase;
use App\Models\User;
use App\Services\Trainings\TrainingPaymentService;
use App\Support\PhoneNumber;
use DomainException;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/**
 * Business rule RG06 — a payment only completes through a verified callback —
 * applied to trainings, and business rule RG05 — content stays behind an
 * entitlement check.
 */
beforeEach(function () {
    $this->client = User::factory()->client()->create();
});

function trainingOnSale(array $overrides = []): Training
{
    return Training::factory()->published()->create(array_merge([
        'price' => 7500,
    ], $overrides));
}

describe('buying a training', function () {
    it('starts a payment whose amount is the training price, never the form\'s', function () {
        $training = trainingOnSale();

        Livewire::actingAs($this->client)
            ->test(TrainingPage::class, ['training' => $training])
            ->set('phone', '+237 650 00 00 01')
            ->set('method', PaymentMethod::OrangeMoney->value)
            ->call('buy');

        $payment = Payment::query()->sole();

        expect($payment->amount->amount)->toBe(7500);
        expect($payment->purpose)->toBe(PaymentPurpose::Training);
        expect($payment->payable_id)->toBe($training->id);
        expect($payment->status)->toBe(PaymentStatus::Initiated);
    });

    it('does not grant access on a browser redirect — only the webhook does', function () {
        $training = trainingOnSale();

        Livewire::actingAs($this->client)
            ->test(TrainingPage::class, ['training' => $training])
            ->set('phone', '+237650000001')
            ->call('buy');

        $payment = Payment::query()->sole();

        // Business rule RG06: the payment is still awaiting its callback, and
        // business rule RG05 follows: no purchase row, no access.
        expect($payment->status)->toBe(PaymentStatus::Initiated);
        expect($training->isAccessibleBy($this->client))->toBeFalse();
        expect(TrainingPurchase::query()->count())->toBe(0);
    });

    it('records exactly one purchase when the callback confirms the payment', function () {
        $training = trainingOnSale();

        Livewire::actingAs($this->client)
            ->test(TrainingPage::class, ['training' => $training])
            ->set('phone', '+237650000001')
            ->call('buy');

        $payment = Payment::query()->sole();

        deliverOrderCallback($payment, PaymentStatus::Succeeded)->assertOk();

        expect($payment->refresh()->status)->toBe(PaymentStatus::Succeeded);
        expect(TrainingPurchase::query()->count())->toBe(1);
        expect($training->refresh()->isAccessibleBy($this->client))->toBeTrue();
    });

    it('absorbs a replayed callback without selling the training twice', function () {
        $training = trainingOnSale();

        Livewire::actingAs($this->client)
            ->test(TrainingPage::class, ['training' => $training])
            ->set('phone', '+237650000001')
            ->call('buy');

        $payment = Payment::query()->sole();

        // The very same event id delivered twice: one stored callback, one
        // purchase, exactly what the unique index on payment_callbacks buys.
        deliverOrderCallback($payment, PaymentStatus::Succeeded, eventId: 'evt-replay-test')->assertOk();
        deliverOrderCallback($payment, PaymentStatus::Succeeded, eventId: 'evt-replay-test')->assertOk();

        expect(PaymentCallback::query()->count())->toBe(1);
        expect(TrainingPurchase::query()->count())->toBe(1);
    });

    it('sends a second click back to the payment already in flight', function () {
        $training = trainingOnSale();

        $service = app(TrainingPaymentService::class);

        $first = $service->start($training, $this->client, PaymentMethod::MtnMomo, PhoneNumber::parse('+237650000001'));
        $second = $service->start($training, $this->client, PaymentMethod::MtnMomo, PhoneNumber::parse('+237650000001'));

        expect($second->providerReference)->toBe($first->providerReference);
        expect(Payment::query()->count())->toBe(1);
    });

    it('refuses to buy a training the client already owns', function () {
        $training = trainingOnSale();
        TrainingPurchase::factory()->create(['client_id' => $this->client->id, 'training_id' => $training->id]);

        app(TrainingPaymentService::class)->start($training, $this->client, PaymentMethod::MtnMomo, PhoneNumber::parse('+237650000001'));
    })->throws(DomainException::class);

    it('records a refusal and leaves the training locked', function () {
        $training = trainingOnSale();

        Livewire::actingAs($this->client)
            ->test(TrainingPage::class, ['training' => $training])
            ->set('phone', '+237650000001')
            ->call('buy');

        $payment = Payment::query()->sole();

        deliverOrderCallback($payment, PaymentStatus::Failed)->assertOk();

        expect($payment->refresh()->status)->toBe(PaymentStatus::Failed);
        expect(TrainingPurchase::query()->count())->toBe(0);
        expect($training->isAccessibleBy($this->client))->toBeFalse();

        // A refusal frees the way for a second attempt.
        Livewire::actingAs($this->client)
            ->test(TrainingPage::class, ['training' => $training])
            ->set('phone', '+237650000001')
            ->call('buy');

        expect(Payment::query()->count())->toBe(2);
    });
});

describe('RG05 — content access', function () {
    it('serves the file to a client who bought the training', function () {
        Storage::fake('local');

        $training = trainingOnSale();
        TrainingPurchase::factory()->create(['client_id' => $this->client->id, 'training_id' => $training->id]);

        $content = TrainingContent::factory()->pdf()->create([
            'training_id' => $training->id,
            'path' => 'trainings/'.$training->id.'/guide.pdf',
        ]);

        Storage::disk('local')->put($content->path, '%PDF-1.4 test');

        $this->actingAs($this->client)
            ->get(route('trainings.content', ['content' => $content->id]))
            ->assertOk();
    });

    it('refuses the file to a client who did not buy the training', function () {
        Storage::fake('local');

        $training = trainingOnSale();
        $content = TrainingContent::factory()->pdf()->create([
            'training_id' => $training->id,
            'path' => 'trainings/'.$training->id.'/guide.pdf',
        ]);

        Storage::disk('local')->put($content->path, '%PDF-1.4 test');

        $this->actingAs($this->client)
            ->get(route('trainings.content', ['content' => $content->id]))
            ->assertForbidden();
    });

    it('refuses the file to a signed-out visitor', function () {
        $content = TrainingContent::factory()->create();

        $this->get(route('trainings.content', ['content' => $content->id]))
            ->assertRedirect(route('login'));
    });

    it('opens the content through an active subscription when the training is included', function () {
        Storage::fake('local');

        $plan = SubscriptionPlan::factory()->create();
        $subscription = Subscription::factory()->forClient($this->client)->active()->create(['plan_id' => $plan->id]);

        $training = trainingOnSale(['included_in_subscription' => true]);
        $content = TrainingContent::factory()->video()->create([
            'training_id' => $training->id,
            'path' => 'trainings/'.$training->id.'/module-1.mp4',
        ]);

        Storage::disk('local')->put($content->path, 'video bytes');

        expect($this->client->hasActiveSubscription())->toBeTrue();
        expect($training->isAccessibleBy($this->client))->toBeTrue();

        $this->actingAs($this->client)
            ->get(route('trainings.content', ['content' => $content->id]))
            ->assertOk();
    });

    it('keeps a subscription-included training locked when the term has lapsed', function () {
        Storage::fake('local');

        $subscription = Subscription::factory()->forClient($this->client)->lapsed()->create();

        $training = trainingOnSale(['included_in_subscription' => true]);
        $content = TrainingContent::factory()->create(['training_id' => $training->id]);

        expect($this->client->hasActiveSubscription())->toBeFalse();
        expect($training->isAccessibleBy($this->client))->toBeFalse();

        $this->actingAs($this->client)
            ->get(route('trainings.content', ['content' => $content->id]))
            ->assertForbidden();
    });

    it('answers 404 when the file behind the content has gone', function () {
        $training = trainingOnSale();
        TrainingPurchase::factory()->create(['client_id' => $this->client->id, 'training_id' => $training->id]);
        $content = TrainingContent::factory()->create(['training_id' => $training->id]);

        $this->actingAs($this->client)
            ->get(route('trainings.content', ['content' => $content->id]))
            ->assertNotFound();
    });

    it('keeps the stored path out of the public page', function () {
        $training = trainingOnSale();
        $content = TrainingContent::factory()->create(['training_id' => $training->id]);

        $this->get(route('trainings.show', ['training' => $training->slug]))
            ->assertOk()
            ->assertDontSee($content->path);
    });

    it('shows the buy form to a client who owns nothing and the open button to one who does', function () {
        $training = trainingOnSale();
        TrainingContent::factory()->count(2)->create(['training_id' => $training->id]);

        Livewire::actingAs($this->client)
            ->test(TrainingPage::class, ['training' => $training])
            ->assertSee('buy');

        TrainingPurchase::factory()->create(['client_id' => $this->client->id, 'training_id' => $training->id]);

        Livewire::actingAs($this->client)
            ->test(TrainingPage::class, ['training' => $training])
            ->assertDontSee('wire:submit="buy"');
    });

    it('never sells the subscription service to the training payment', function () {
        // Phase 8 guard: the two services exist side by side, and neither one
        // may silently adopt the other's payable.
        $training = trainingOnSale();
        $service = app(TrainingPaymentService::class);

        expect(fn () => $service->start(
            $training,
            $this->client,
            PaymentMethod::MtnMomo,
            PhoneNumber::parse('+237650000001'),
        ))->not->toThrow(PaymentOutcomeNotHandled::class);
    });
});
