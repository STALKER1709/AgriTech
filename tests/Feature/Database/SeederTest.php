<?php

declare(strict_types=1);

use App\Enums\OrderStatus;
use App\Enums\PublicationStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\Privilege;
use App\Models\Product;
use App\Models\Setting;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Support\Money;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\SuperAdminSeeder;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

it('creates a super administrator holding every privilege', function () {
    $admin = User::query()->where('email', SuperAdminSeeder::EMAIL)->first();

    expect($admin)->not->toBeNull();
    expect($admin->role)->toBe(UserRole::Admin);
    expect($admin->status)->toBe(UserStatus::Active);
    expect($admin->privileges)->toHaveCount(count(Privilege::catalogue()));
    expect($admin->hasPrivilege(Privilege::APPROVE_FARMERS))->toBeTrue();
});

it('seeds the reference data', function () {
    expect(Category::query()->count())->toBe(10);
    expect(SubscriptionPlan::query()->count())->toBe(3);
    expect(Setting::query()->count())->toBe(5);
});

it('seeds the platform settings with usable values', function () {
    $fee = Setting::query()->where('key', Setting::FARMER_REGISTRATION_FEE)->firstOrFail();
    $rate = Setting::query()->where('key', Setting::PLATFORM_COMMISSION_RATE)->firstOrFail();
    $moderation = Setting::query()->where('key', Setting::PRIOR_MODERATION_ENABLED)->firstOrFail();

    expect($fee->integerValue())->toBe(10_000);
    expect($rate->integerValue())->toBe(5);
    expect($moderation->booleanValue())->toBeTrue();
    expect($moderation->typedValue())->toBeBool();
    expect($fee->typedValue())->toBeInt();
});

it('seeds a farmer account at every stage of the sign-up path', function () {
    $statuses = User::query()
        ->where('role', UserRole::Farmer)
        ->pluck('status')
        ->map(fn (UserStatus $status): string => $status->value)
        ->all();

    expect($statuses)->toContain(
        UserStatus::Active->value,
        UserStatus::PendingValidation->value,
        UserStatus::PendingPayment->value,
        UserStatus::Rejected->value,
    );
});

it('leaves one publication awaiting moderation', function () {
    expect(Product::query()->where('status', PublicationStatus::InReview)->count())->toBe(1);
    expect(Product::query()->published()->count())->toBeGreaterThan(0);
});

it('splits a multi-farmer order into one sub-order per farmer', function () {
    $order = Order::query()
        ->withCount('subOrders')
        ->orderByDesc('sub_orders_count')
        ->first();

    expect($order->sub_orders_count)->toBeGreaterThan(1);
    expect($order->subOrders->pluck('farmer_id')->unique())->toHaveCount($order->sub_orders_count);
});

it('keeps every order total equal to the sum of its sub-orders', function () {
    Order::query()->with('subOrders.items')->each(function (Order $order): void {
        $fromSubOrders = Money::sum($order->subOrders->map(
            fn ($subOrder): Money => $subOrder->subtotal_amount,
        ));

        expect($order->total_amount->amount)
            ->toBe($fromSubOrders->amount, "Order {$order->reference} does not match its sub-orders.");

        foreach ($order->subOrders as $subOrder) {
            $fromItems = Money::sum($subOrder->items->map(fn ($item): Money => $item->line_total));

            expect($subOrder->subtotal_amount->amount)
                ->toBe($fromItems->amount, "Sub-order {$subOrder->reference} does not match its lines.");

            expect($subOrder->commission_amount->amount)
                ->toBe($subOrder->subtotal_amount->percentage($subOrder->commission_rate_snapshot)->amount);
        }
    });
});

it('computes each order line from its price snapshot and quantity', function () {
    Order::query()->with('subOrders.items')->each(function (Order $order): void {
        foreach ($order->subOrders as $subOrder) {
            foreach ($subOrder->items as $item) {
                expect($item->line_total->amount)->toBe($item->computeLineTotal()->amount);
            }
        }
    });
});

it('seeds orders in several states', function () {
    $statuses = Order::query()->pluck('status')->map(fn (OrderStatus $s): string => $s->value)->all();

    expect($statuses)->toContain(
        OrderStatus::Paid->value,
        OrderStatus::PendingPayment->value,
        OrderStatus::Cancelled->value,
    );
});

it('can be seeded twice without creating duplicates', function () {
    $before = [
        'users' => User::query()->count(),
        'categories' => Category::query()->count(),
        'privileges' => Privilege::query()->count(),
        'settings' => Setting::query()->count(),
        'plans' => SubscriptionPlan::query()->count(),
        'products' => Product::query()->count(),
    ];

    $this->seed(DatabaseSeeder::class);

    expect(User::query()->count())->toBe($before['users']);
    expect(Category::query()->count())->toBe($before['categories']);
    expect(Privilege::query()->count())->toBe($before['privileges']);
    expect(Setting::query()->count())->toBe($before['settings']);
    expect(SubscriptionPlan::query()->count())->toBe($before['plans']);
    expect(Product::query()->count())->toBe($before['products']);
});
