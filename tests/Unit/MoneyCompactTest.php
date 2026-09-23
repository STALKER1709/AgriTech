<?php

declare(strict_types=1);

use App\Support\Money;

/**
 * L'écriture courte des montants, celle des étiquettes de graphique.
 */
it('leaves small amounts alone', function (int $amount, string $expected) {
    expect(Money::fromInteger($amount)->formatCompact())->toBe($expected);
})->with([
    [0, '0'],
    [1, '1'],
    [850, '850'],
    [999, '999'],
]);

it('folds thousands and millions', function (int $amount, string $expected) {
    expect(Money::fromInteger($amount)->formatCompact())->toBe($expected);
})->with([
    'pile' => [8_000, '8'."\u{00A0}".'k'],
    'une décimale utile' => [72_500, '72,5'."\u{00A0}".'k'],
    'au-delà de cent, pas de décimale' => [336_000, '336'."\u{00A0}".'k'],
    'arrondi' => [336_400, '336'."\u{00A0}".'k'],
    'million' => [1_200_000, '1,2'."\u{00A0}".'M'],
]);

it('keeps the sign', function () {
    expect(Money::fromInteger(-72_500)->formatCompact())->toBe('-72,5'."\u{00A0}".'k');
});

it('never pretends to be the exact amount', function () {
    // L'écriture courte arrondit ; `format()` reste la référence, et les deux
    // ne doivent pas être confondues.
    $money = Money::fromInteger(336_400);

    expect($money->formatCompact())->not->toBe($money->format());
    expect($money->format())->toBe('336'."\u{00A0}".'400'."\u{00A0}".'FCFA');
});
