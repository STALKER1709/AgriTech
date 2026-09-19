<?php

declare(strict_types=1);

use App\Support\Quantity;

it('parses decimal strings up to three places', function (string $input, int $thousandths) {
    expect(Quantity::fromString($input)->thousandths)->toBe($thousandths);
})->with([
    'whole number' => ['12', 12_000],
    'one decimal' => ['12.5', 12_500],
    'three decimals' => ['0.125', 125],
    'trailing zeros' => ['3.000', 3_000],
    'zero' => ['0', 0],
    'negative' => ['-2.5', -2_500],
    'surrounding spaces' => [' 7.25 ', 7_250],
]);

it('refuses anything that is not a three-decimal number', function (string $input) {
    Quantity::fromString($input);
})->with([
    'too many decimals' => ['1.2345'],
    'not a number' => ['douze'],
    'empty' => [''],
    'comma as separator' => ['12,5'],
    'scientific notation' => ['1e3'],
])->throws(InvalidArgumentException::class);

it('renders the canonical decimal form for the database', function (string $input, string $expected) {
    expect(Quantity::fromString($input)->toDecimalString())->toBe($expected);
})->with([
    'whole' => ['12', '12.000'],
    'half' => ['12.5', '12.500'],
    'small' => ['0.125', '0.125'],
    'negative' => ['-2.5', '-2.500'],
    'zero' => ['0', '0.000'],
]);

it('renders a French display form without trailing zeros', function (string $input, string $expected) {
    expect(Quantity::fromString($input)->format())->toBe($expected);
})->with([
    'whole' => ['12.000', '12'],
    'half' => ['12.5', '12,5'],
    'three decimals' => ['0.125', '0,125'],
    'zero' => ['0', '0'],
]);

it('adds and subtracts exactly', function () {
    $a = Quantity::fromString('12.5');
    $b = Quantity::fromString('0.75');

    expect($a->plus($b)->toDecimalString())->toBe('13.250');
    expect($a->minus($b)->toDecimalString())->toBe('11.750');
});

it('compares quantities written differently but equal', function () {
    expect(Quantity::fromString('12.5')->equals(Quantity::fromString('12.500')))->toBeTrue();
    expect(Quantity::fromString('12.5')->isGreaterThanOrEqualTo(Quantity::fromString('12.500')))->toBeTrue();
    expect(Quantity::fromString('12.501')->isGreaterThan(Quantity::fromString('12.5')))->toBeTrue();
    expect(Quantity::fromString('12.499')->isLessThan(Quantity::fromString('12.5')))->toBeTrue();
});

it('reports its sign', function () {
    expect(Quantity::zero()->isZero())->toBeTrue();
    expect(Quantity::fromInteger(3)->isPositive())->toBeTrue();
    expect(Quantity::fromString('-0.001')->isNegative())->toBeTrue();
});

it('survives a round trip through the database format', function () {
    $original = Quantity::fromString('850.5');

    expect(Quantity::fromString($original->toDecimalString())->equals($original))->toBeTrue();
});
