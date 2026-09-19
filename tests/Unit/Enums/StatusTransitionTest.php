<?php

declare(strict_types=1);

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PublicationStatus;
use App\Enums\SubOrderStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\UserStatus;

it('gives every status a French label', function (string $enum) {
    foreach ($enum::cases() as $case) {
        expect($case->label())->toBeString()->not->toBeEmpty();
    }
})->with([
    UserStatus::class,
    PublicationStatus::class,
    OrderStatus::class,
    SubOrderStatus::class,
    PaymentStatus::class,
    SubscriptionStatus::class,
]);

describe('user account', function () {
    it('walks the farmer sign-up path', function () {
        expect(UserStatus::PendingPayment->canTransitionTo(UserStatus::PendingValidation))->toBeTrue();
        expect(UserStatus::PendingValidation->canTransitionTo(UserStatus::Active))->toBeTrue();
        expect(UserStatus::PendingValidation->canTransitionTo(UserStatus::Rejected))->toBeTrue();
    });

    it('refuses to skip the validation step', function () {
        expect(UserStatus::PendingPayment->canTransitionTo(UserStatus::Active))->toBeFalse();
    });

    it('treats deletion as final, per business rule RG08', function () {
        expect(UserStatus::Deleted->allowedTransitions())->toBe([]);

        foreach (UserStatus::cases() as $target) {
            expect(UserStatus::Deleted->canTransitionTo($target))->toBeFalse();
        }
    });

    it('lets a suspended account come back', function () {
        expect(UserStatus::Suspended->canTransitionTo(UserStatus::Active))->toBeTrue();
    });
});

describe('publication', function () {
    it('reaches publication through moderation, per business rule RG09', function () {
        expect(PublicationStatus::Draft->canTransitionTo(PublicationStatus::InReview))->toBeTrue();
        expect(PublicationStatus::InReview->canTransitionTo(PublicationStatus::Published))->toBeTrue();
        expect(PublicationStatus::InReview->canTransitionTo(PublicationStatus::Rejected))->toBeTrue();
    });

    it('allows direct publication when prior moderation is switched off', function () {
        expect(PublicationStatus::Draft->canTransitionTo(PublicationStatus::Published))->toBeTrue();
    });

    it('never publishes a rejected item without a new review', function () {
        expect(PublicationStatus::Rejected->canTransitionTo(PublicationStatus::Published))->toBeFalse();
    });

    it('only shows published items to the public', function () {
        expect(PublicationStatus::Published->isVisibleToPublic())->toBeTrue();

        foreach ([PublicationStatus::Draft, PublicationStatus::InReview, PublicationStatus::Rejected, PublicationStatus::Archived] as $hidden) {
            expect($hidden->isVisibleToPublic())->toBeFalse();
        }
    });
});

describe('order', function () {
    it('follows the fulfilment path', function () {
        expect(OrderStatus::PendingPayment->canTransitionTo(OrderStatus::Paid))->toBeTrue();
        expect(OrderStatus::Paid->canTransitionTo(OrderStatus::Preparing))->toBeTrue();
        expect(OrderStatus::Preparing->canTransitionTo(OrderStatus::Delivered))->toBeTrue();
    });

    it('never reopens a delivered or cancelled order', function () {
        expect(OrderStatus::Delivered->isFinal())->toBeTrue();
        expect(OrderStatus::Cancelled->isFinal())->toBeTrue();
        expect(OrderStatus::Cancelled->canTransitionTo(OrderStatus::Paid))->toBeFalse();
    });

    it('keeps sub-orders on the same rails as orders', function () {
        foreach (OrderStatus::cases() as $case) {
            $mirror = SubOrderStatus::from($case->value);

            expect(array_map(
                fn (OrderStatus $target): string => $target->value,
                $case->allowedTransitions(),
            ))->toBe(array_map(
                fn (SubOrderStatus $target): string => $target->value,
                $mirror->allowedTransitions(),
            ));
        }
    });
});

describe('payment', function () {
    it('accepts an outcome from either waiting state', function () {
        foreach ([PaymentStatus::Initiated, PaymentStatus::Pending] as $waiting) {
            expect($waiting->isAwaitingOutcome())->toBeTrue();
            expect($waiting->canTransitionTo(PaymentStatus::Succeeded))->toBeTrue();
            expect($waiting->canTransitionTo(PaymentStatus::Failed))->toBeTrue();
            expect($waiting->canTransitionTo(PaymentStatus::Expired))->toBeTrue();
        }
    });

    it('never revives a failed or expired payment', function () {
        expect(PaymentStatus::Failed->canTransitionTo(PaymentStatus::Succeeded))->toBeFalse();
        expect(PaymentStatus::Expired->canTransitionTo(PaymentStatus::Succeeded))->toBeFalse();
    });

    it('only allows a refund once the payment succeeded', function () {
        expect(PaymentStatus::Succeeded->canTransitionTo(PaymentStatus::Refunded))->toBeTrue();
        expect(PaymentStatus::Pending->canTransitionTo(PaymentStatus::Refunded))->toBeFalse();
        expect(PaymentStatus::Refunded->isFinal())->toBeTrue();
    });
});

describe('subscription', function () {
    it('activates only after payment', function () {
        expect(SubscriptionStatus::PendingPayment->canTransitionTo(SubscriptionStatus::Active))->toBeTrue();
        expect(SubscriptionStatus::Expired->canTransitionTo(SubscriptionStatus::Active))->toBeFalse();
    });

    it('grants access only while active', function () {
        expect(SubscriptionStatus::Active->grantsAccess())->toBeTrue();
        expect(SubscriptionStatus::Expired->grantsAccess())->toBeFalse();
        expect(SubscriptionStatus::PendingPayment->grantsAccess())->toBeFalse();
        expect(SubscriptionStatus::Cancelled->grantsAccess())->toBeFalse();
    });
});

it('never allows a status to transition to itself', function (string $enum) {
    foreach ($enum::cases() as $case) {
        expect($case->canTransitionTo($case))->toBeFalse();
    }
})->with([
    UserStatus::class,
    OrderStatus::class,
    SubOrderStatus::class,
    PaymentStatus::class,
    SubscriptionStatus::class,
]);
