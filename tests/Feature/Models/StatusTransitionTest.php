<?php

declare(strict_types=1);

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PublicationStatus;
use App\Enums\SubOrderStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\UserStatus;
use App\Exceptions\InvalidStatusTransition;
use App\Models\FarmerProfile;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\SubOrder;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Training;
use App\Models\User;

describe('farmer account', function () {
    it('moves from awaiting payment to awaiting validation and on to active', function () {
        $admin = User::factory()->admin()->create();
        $farmer = User::factory()->awaitingPayment()->create();
        FarmerProfile::factory()->create(['user_id' => $farmer->id]);

        $farmer->markAwaitingValidation();
        expect($farmer->refresh()->status)->toBe(UserStatus::PendingValidation);

        $farmer->approve($admin);
        expect($farmer->refresh()->status)->toBe(UserStatus::Active);
        expect($farmer->farmerProfile->refresh()->isValidated())->toBeTrue();
        expect($farmer->farmerProfile->validated_by)->toBe($admin->id);
    });

    it('refuses to activate an account that never paid, per business rule RG02', function () {
        $farmer = User::factory()->awaitingPayment()->create();

        expect(fn () => $farmer->transitionTo(UserStatus::Active))
            ->toThrow(InvalidStatusTransition::class);

        expect($farmer->refresh()->status)->toBe(UserStatus::PendingPayment);
    });

    it('records the reason when an account is rejected', function () {
        $admin = User::factory()->admin()->create();
        $farmer = User::factory()->awaitingValidation()->create();
        FarmerProfile::factory()->create(['user_id' => $farmer->id]);

        $farmer->reject($admin, 'Justificatifs illisibles.');

        expect($farmer->refresh()->status)->toBe(UserStatus::Rejected);
        expect($farmer->farmerProfile->refresh()->rejection_reason)->toBe('Justificatifs illisibles.');
        expect($farmer->farmerProfile->isValidated())->toBeFalse();
    });

    it('suspends and reinstates an active account', function () {
        $farmer = User::factory()->farmer()->create();

        $farmer->suspend();
        expect($farmer->refresh()->status)->toBe(UserStatus::Suspended);

        $farmer->reinstate();
        expect($farmer->refresh()->status)->toBe(UserStatus::Active);
    });

    it('never brings a deleted account back, per business rule RG08', function () {
        $user = User::factory()->create(['status' => UserStatus::Active]);
        $user->transitionTo(UserStatus::Deleted);

        expect(fn () => $user->transitionTo(UserStatus::Active))
            ->toThrow(InvalidStatusTransition::class);
    });

    it('only lets an active farmer publish, per business rule RG01', function () {
        expect(User::factory()->farmer()->create()->canPublish())->toBeTrue();
        expect(User::factory()->awaitingValidation()->create()->canPublish())->toBeFalse();
        expect(User::factory()->awaitingPayment()->create()->canPublish())->toBeFalse();
        expect(User::factory()->farmer()->suspended()->create()->canPublish())->toBeFalse();
        expect(User::factory()->client()->create()->canPublish())->toBeFalse();
    });
});

describe('publication', function () {
    it('walks a product through moderation', function () {
        $product = Product::factory()->draft()->create();

        $product->submitForReview();
        expect($product->refresh()->status)->toBe(PublicationStatus::InReview);

        $product->publish();
        expect($product->refresh()->status)->toBe(PublicationStatus::Published);
    });

    it('refuses to publish a rejected product without a new review', function () {
        $product = Product::factory()->rejected()->create();

        expect(fn () => $product->publish())->toThrow(InvalidStatusTransition::class);
        expect($product->refresh()->status)->toBe(PublicationStatus::Rejected);
    });

    it('walks a training through moderation too', function () {
        $training = Training::factory()->draft()->create();

        $training->submitForReview();
        $training->publish();

        expect($training->refresh()->status)->toBe(PublicationStatus::Published);
    });

    it('only lists published items in the public scope', function () {
        Product::factory()->published()->count(2)->create();
        Product::factory()->draft()->create();
        Product::factory()->inReview()->create();
        Product::factory()->archived()->create();

        expect(Product::query()->published()->count())->toBe(2);
    });
});

describe('order', function () {
    it('walks an order and its sub-orders to delivery', function () {
        $order = Order::factory()->create();
        $subOrder = SubOrder::factory()->create(['order_id' => $order->id]);

        $order->markAsPaid();
        $subOrder->markAsPaid();
        expect($order->refresh()->status)->toBe(OrderStatus::Paid);
        expect($subOrder->refresh()->status)->toBe(SubOrderStatus::Paid);

        $order->markAsPreparing();
        $order->markAsDelivered();
        expect($order->refresh()->status)->toBe(OrderStatus::Delivered);
    });

    it('never pays a cancelled order', function () {
        $order = Order::factory()->cancelled()->create();

        expect(fn () => $order->markAsPaid())->toThrow(InvalidStatusTransition::class);
    });

    it('flags unpaid orders past their window', function () {
        Order::factory()->expired()->count(2)->create();
        Order::factory()->awaitingPayment()->create();
        Order::factory()->paid()->create();

        expect(Order::query()->expired()->count())->toBe(2);
        expect(Order::factory()->expired()->create()->hasExpired())->toBeTrue();
        expect(Order::factory()->awaitingPayment()->create()->hasExpired())->toBeFalse();
    });

    it('builds sequential references that do not collide', function () {
        $first = Order::create([
            'reference' => Order::nextReference(),
            'client_id' => User::factory()->client()->create()->id,
            'total_amount' => 1_000,
            'status' => OrderStatus::PendingPayment,
        ]);

        $second = Order::create([
            'reference' => Order::nextReference(),
            'client_id' => User::factory()->client()->create()->id,
            'total_amount' => 1_000,
            'status' => OrderStatus::PendingPayment,
        ]);

        expect($first->reference)->toBe(sprintf('CMD-%d-000001', now()->year));
        expect($second->reference)->toBe(sprintf('CMD-%d-000002', now()->year));
    });
});

describe('payment', function () {
    it('stamps the confirmation time when it succeeds, per business rule RG06', function () {
        $payment = Payment::factory()->create();

        expect($payment->confirmed_at)->toBeNull();

        $payment->markAsPending();
        $payment->markAsSucceeded();

        expect($payment->refresh()->status)->toBe(PaymentStatus::Succeeded);
        expect($payment->confirmed_at)->not->toBeNull();
        expect($payment->isSuccessful())->toBeTrue();
    });

    it('never turns a failed payment into a successful one', function () {
        $payment = Payment::factory()->failed()->create();

        expect(fn () => $payment->markAsSucceeded())->toThrow(InvalidStatusTransition::class);
    });

    it('never refunds a payment that never succeeded', function () {
        $payment = Payment::factory()->pending()->create();

        expect(fn () => $payment->markAsRefunded())->toThrow(InvalidStatusTransition::class);
    });

    it('lists the payments the reconciliation task has to chase', function () {
        Payment::factory()->create();
        Payment::factory()->pending()->create();
        Payment::factory()->succeeded()->create();
        Payment::factory()->failed()->create();

        expect(Payment::query()->awaitingOutcome()->count())->toBe(2);
    });
});

describe('subscription', function () {
    it('derives the end date from the plan when it activates', function () {
        $plan = SubscriptionPlan::factory()->create(['duration_days' => 90]);
        $subscription = Subscription::factory()->create(['plan_id' => $plan->id]);

        $subscription->activate();

        expect($subscription->refresh()->status)->toBe(SubscriptionStatus::Active);
        expect($subscription->starts_at)->not->toBeNull();
        expect((int) $subscription->starts_at->diffInDays($subscription->ends_at))->toBe(90);
        expect($subscription->isCurrentlyActive())->toBeTrue();
    });

    it('refuses to activate an expired subscription', function () {
        $subscription = Subscription::factory()->expired()->create();

        expect(fn () => $subscription->activate())->toThrow(InvalidStatusTransition::class);
    });

    it('does not grant access once the term has passed', function () {
        $subscription = Subscription::factory()->lapsed()->create();

        expect($subscription->status)->toBe(SubscriptionStatus::Active);
        expect($subscription->isCurrentlyActive())->toBeFalse();
        expect($subscription->hasLapsed())->toBeTrue();
        expect(Subscription::query()->lapsed()->count())->toBe(1);
    });
});
