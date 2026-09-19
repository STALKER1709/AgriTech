<?php

declare(strict_types=1);

use App\Support\PhoneNumber;

it('reads a number however it was written', function (string $input) {
    expect(PhoneNumber::parse($input)->toE164())->toBe('+237650000001');
})->with([
    'bare national' => ['650000001'],
    'international' => ['+237650000001'],
    'country code without plus' => ['237650000001'],
    'international prefix' => ['00237650000001'],
    'spaced' => ['650 00 00 01'],
    'dashed' => ['+237 650-00-00-01'],
    'dotted' => ['6.50.00.00.01'],
    'surrounding spaces' => ['  650000001  '],
]);

it('accepts a landline, which starts with 2', function () {
    $number = PhoneNumber::parse('222222222');

    expect($number->toE164())->toBe('+237222222222');
    expect($number->isMobile())->toBeFalse();
});

it('does not mistake a landline prefix for the country code', function () {
    // A landline national number starts with 2, like the country code, so the
    // prefix may only be stripped when the remaining length is right.
    expect(PhoneNumber::parse('237222222')->toE164())->toBe('+237237222222');
});

it('flags mobile numbers', function () {
    expect(PhoneNumber::parse('650000001')->isMobile())->toBeTrue();
    expect(PhoneNumber::parse('222222222')->isMobile())->toBeFalse();
});

it('refuses anything that is not a Cameroonian number', function (string $input) {
    expect(PhoneNumber::tryParse($input))->toBeNull();
    expect(PhoneNumber::isValid($input))->toBeFalse();
})->with([
    'empty' => [''],
    'too short' => ['12345'],
    'too long' => ['6500000012'],
    'unknown national prefix' => ['750000001'],
    'leading zero' => ['0650000001'],
    'another country' => ['+33650000001'],
    'letters only' => ['abcdefghi'],
]);

it('throws when parsing an invalid number', function () {
    PhoneNumber::parse('12345');
})->throws(InvalidArgumentException::class);

it('formats for display', function () {
    $number = PhoneNumber::parse('+237650000001');

    expect($number->format())->toBe('650 00 00 01');
    expect($number->formatInternational())->toBe('+237 650 00 00 01');
    expect((string) $number)->toBe('+237650000001');
});

it('treats two writings of the same number as equal', function () {
    expect(PhoneNumber::parse('650000001')->equals(PhoneNumber::parse('+237 650 00 00 01')))->toBeTrue();
    expect(PhoneNumber::parse('650000001')->equals(PhoneNumber::parse('650000002')))->toBeFalse();
});
