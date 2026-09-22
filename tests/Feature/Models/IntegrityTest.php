<?php

declare(strict_types=1);

use App\Models\Conversation;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\Training;
use App\Models\TrainingPurchase;
use App\Models\User;
use App\Support\Money;
use App\Support\Quantity;
use Illuminate\Database\QueryException;

describe('uniqueness', function () {
    it('refuses a duplicate email address', function () {
        User::factory()->create(['email' => 'clarisse@agritech.local']);

        User::factory()->create(['email' => 'clarisse@agritech.local']);
    })->throws(QueryException::class);

    it('refuses a duplicate phone number', function () {
        User::factory()->create(['phone' => '+237650000099']);

        User::factory()->create(['phone' => '+237650000099']);
    })->throws(QueryException::class);

    it('refuses buying the same training twice', function () {
        $client = User::factory()->client()->create();
        $training = Training::factory()->create();

        TrainingPurchase::factory()->create(['client_id' => $client->id, 'training_id' => $training->id]);
        TrainingPurchase::factory()->create(['client_id' => $client->id, 'training_id' => $training->id]);
    })->throws(QueryException::class);

    it('keeps one conversation per client and farmer pair', function () {
        $client = User::factory()->client()->create();
        $farmer = User::factory()->farmer()->create();

        Conversation::factory()->between($client, $farmer)->create();
        Conversation::factory()->between($client, $farmer)->create();
    })->throws(QueryException::class);

    it('refuses a repeated idempotency key, which is what makes the webhook safe', function () {
        Payment::factory()->create(['idempotency_key' => 'callback-42']);

        Payment::factory()->create(['idempotency_key' => 'callback-42']);
    })->throws(QueryException::class);

    it('refuses a repeated provider reference', function () {
        Payment::factory()->create(['provider_reference' => 'PAY-DUPLICATE']);

        Payment::factory()->create(['provider_reference' => 'PAY-DUPLICATE']);
    })->throws(QueryException::class);
});

describe('money handling, business rule RG10', function () {
    it('stores no money column as a float', function () {
        $moneyColumns = [
            'products' => ['unit_price'],
            'trainings' => ['price'],
            'orders' => ['total_amount'],
            'sub_orders' => ['subtotal_amount', 'commission_amount'],
            'order_items' => ['unit_price_snapshot', 'line_total'],
            'training_purchases' => ['amount'],
            'subscription_plans' => ['price'],
            'payments' => ['amount'],
        ];

        $database = config('database.connections.mysql.database');

        foreach ($moneyColumns as $table => $columns) {
            foreach ($columns as $column) {
                $type = DB::selectOne(
                    'select DATA_TYPE as data_type from information_schema.COLUMNS
                     where TABLE_SCHEMA = ? and TABLE_NAME = ? and COLUMN_NAME = ?',
                    [$database, $table, $column],
                )?->data_type;

                expect($type)->not->toBeNull("Column {$table}.{$column} is missing.");
                expect($type)->toBeIn(
                    ['int', 'integer', 'bigint', 'smallint', 'mediumint'],
                    "Column {$table}.{$column} must hold an integer, got [{$type}].",
                );
            }
        }
    });

    it('hands money back as a Money object', function () {
        $product = Product::factory()->create(['unit_price' => Money::fromInteger(2_500)]);

        expect($product->refresh()->unit_price)->toBeInstanceOf(Money::class);
        expect($product->unit_price->amount)->toBeMoney()->toBe(2_500);
        expect($product->unit_price->format())->toBe("2\u{00A0}500\u{00A0}FCFA");
    });

    it('accepts a plain integer as money too', function () {
        $product = Product::factory()->create(['unit_price' => 1_500]);

        expect($product->refresh()->unit_price->amount)->toBe(1_500);
    });

    it('refuses a float where money is expected', function () {
        Product::factory()->create(['unit_price' => 1_500.75]);
    })->throws(InvalidArgumentException::class);
});

describe('quantity handling', function () {
    it('hands quantities back as a Quantity object', function () {
        $product = Product::factory()->create(['stock_quantity' => Quantity::fromString('850.5')]);

        expect($product->refresh()->stock_quantity)->toBeInstanceOf(Quantity::class);
        expect($product->stock_quantity->toDecimalString())->toBe('850.500');
    });

    it('refuses a float where a quantity is expected', function () {
        Product::factory()->create(['stock_quantity' => 12.5]);
    })->throws(InvalidArgumentException::class);

    it('answers whether the stock covers a requested quantity, per business rule RG03', function () {
        $product = Product::factory()->withStock(Quantity::fromString('12.5'))->create();

        expect($product->hasStockFor(Quantity::fromString('12.5')))->toBeTrue();
        expect($product->hasStockFor(Quantity::fromString('12.499')))->toBeTrue();
        expect($product->hasStockFor(Quantity::fromString('12.501')))->toBeFalse();
        expect($product->isInStock())->toBeTrue();

        expect(Product::factory()->outOfStock()->create()->isInStock())->toBeFalse();
    });
});

describe('training access, business rule RG05', function () {
    it('opens a training to the client who bought it', function () {
        $client = User::factory()->client()->create();
        $training = Training::factory()->create();

        expect($training->isAccessibleBy($client))->toBeFalse();

        TrainingPurchase::factory()->create(['client_id' => $client->id, 'training_id' => $training->id]);

        expect($training->isAccessibleBy($client))->toBeTrue();
    });

    it('opens an included training to an active subscriber', function () {
        $client = User::factory()->client()->create();
        $training = Training::factory()->includedInSubscription()->create();

        expect($training->isAccessibleBy($client))->toBeFalse();

        Subscription::factory()->forClient($client)->active()->create();

        expect($training->isAccessibleBy($client))->toBeTrue();
    });

    it('keeps a training outside the subscription closed to subscribers', function () {
        $client = User::factory()->client()->create();
        $training = Training::factory()->create(['included_in_subscription' => false]);

        Subscription::factory()->forClient($client)->active()->create();

        expect($training->isAccessibleBy($client))->toBeFalse();
    });

    it('closes an included training once the subscription has lapsed', function () {
        $client = User::factory()->client()->create();
        $training = Training::factory()->includedInSubscription()->create();

        Subscription::factory()->forClient($client)->lapsed()->create();

        expect($training->isAccessibleBy($client))->toBeFalse();
    });
});
