<?php

declare(strict_types=1);

use App\Support\Money;
use App\Support\Quantity;

it('holds a whole number of francs', function () {
    expect(Money::fromInteger(12_500)->amount)->toBeMoney()->toBe(12_500);
    expect(Money::zero()->amount)->toBe(0);
});

it('adds and subtracts without drift', function () {
    $a = Money::fromInteger(12_500);
    $b = Money::fromInteger(2_500);

    expect($a->plus($b)->amount)->toBe(15_000);
    expect($a->minus($b)->amount)->toBe(10_000);
});

it('multiplies by a whole factor', function () {
    expect(Money::fromInteger(2_500)->multipliedBy(4)->amount)->toBe(10_000);
});

it('multiplies by a fractional quantity, rounding half up', function (string $quantity, int $unitPrice, int $expected) {
    $result = Money::fromInteger($unitPrice)->multipliedByQuantity(Quantity::fromString($quantity));

    expect($result->amount)->toBeMoney()->toBe($expected);
})->with([
    'whole quantity' => ['12', 1_000, 12_000],
    'half unit' => ['12.5', 1_000, 12_500],
    'rounds half up' => ['0.333', 1_500, 500],
    'rounds down below half' => ['0.001', 400, 0],
    'rounds up above half' => ['0.002', 400, 1],
]);

it('takes a percentage, rounding half up', function (int $amount, int $percent, int $expected) {
    expect(Money::fromInteger($amount)->percentage($percent)->amount)->toBe($expected);
})->with([
    'five percent of a round sum' => [10_000, 5, 500],
    'zero percent' => [10_000, 0, 0],
    'rounds half up' => [1_050, 5, 53],
    'full amount' => [7_321, 100, 7_321],
]);

it('refuses a negative percentage', function () {
    Money::fromInteger(1_000)->percentage(-5);
})->throws(InvalidArgumentException::class);

it('sums a collection of amounts', function () {
    $total = Money::sum([
        Money::fromInteger(1_000),
        Money::fromInteger(2_500),
        Money::fromInteger(7_000),
    ]);

    expect($total->amount)->toBe(10_500);
    expect(Money::sum([])->amount)->toBe(0);
});

it('compares amounts', function () {
    $small = Money::fromInteger(100);
    $large = Money::fromInteger(500);

    expect($small->isLessThan($large))->toBeTrue();
    expect($large->isGreaterThan($small))->toBeTrue();
    expect($small->equals(Money::fromInteger(100)))->toBeTrue();
    expect(Money::zero()->isZero())->toBeTrue();
    expect($small->isPositive())->toBeTrue();
    expect(Money::fromInteger(-1)->isNegative())->toBeTrue();
});

it('formats amounts the way the interface shows them', function (int $amount, string $expected) {
    expect(Money::fromInteger($amount)->format())->toBe($expected);
})->with([
    // U+00A0 groups the thousands and keeps the currency on the same line (maquettes Stitch).
    'units' => [500, "500\u{00A0}FCFA"],
    'thousands' => [12_500, "12\u{00A0}500\u{00A0}FCFA"],
    'millions' => [1_234_567, "1\u{00A0}234\u{00A0}567\u{00A0}FCFA"],
    'zero' => [0, "0\u{00A0}FCFA"],
    'negative' => [-2_500, "-2\u{00A0}500\u{00A0}FCFA"],
]);

it('serialises to its integer amount', function () {
    expect(json_encode(['price' => Money::fromInteger(12_500)]))->toBe('{"price":12500}');
});
