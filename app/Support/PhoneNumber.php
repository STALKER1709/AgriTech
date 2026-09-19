<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;
use Stringable;

/**
 * A Cameroonian phone number, held in one canonical shape.
 *
 * People write the same number half a dozen ways — "650 00 00 01",
 * "+237 650000001", "00237-650-000-001". Storing whatever was typed would make
 * the unique constraint on users.phone meaningless, so every number is
 * normalised to +237XXXXXXXXX before it reaches the database.
 *
 * The accepted shape is nine national digits starting with 6 (mobile) or 2
 * (landline). See DECISIONS.md: this is a documented assumption rather than a
 * rule confirmed against an authoritative source, which is why it lives in one
 * class and one regular expression.
 *
 * @immutable
 */
final readonly class PhoneNumber implements Stringable
{
    public const string COUNTRY_CODE = '237';

    private const string NATIONAL_PATTERN = '/^[26]\d{8}$/';

    private const int NATIONAL_LENGTH = 9;

    /**
     * @param  string  $national  the nine national digits, without country code
     */
    private function __construct(public string $national) {}

    /**
     * Parse a number, or throw when it cannot be read as a Cameroonian one.
     */
    public static function parse(string $value): self
    {
        return self::tryParse($value) ?? throw new InvalidArgumentException(
            sprintf('[%s] is not a valid Cameroonian phone number.', $value),
        );
    }

    /**
     * Parse a number, or return null when it cannot be read.
     */
    public static function tryParse(string $value): ?self
    {
        $digits = preg_replace('/\D/', '', $value) ?? '';

        $national = match (true) {
            // 00237 followed by the national number.
            str_starts_with($digits, '00'.self::COUNTRY_CODE)
                && strlen($digits) === 5 + self::NATIONAL_LENGTH => substr($digits, 5),

            // +237 or 237 followed by the national number. The length check
            // matters: a landline already starts with 2, so the prefix can
            // only be stripped when what remains is the right size.
            str_starts_with($digits, self::COUNTRY_CODE)
                && strlen($digits) === 3 + self::NATIONAL_LENGTH => substr($digits, 3),

            strlen($digits) === self::NATIONAL_LENGTH => $digits,

            default => null,
        };

        if ($national === null || preg_match(self::NATIONAL_PATTERN, $national) !== 1) {
            return null;
        }

        return new self($national);
    }

    public static function isValid(string $value): bool
    {
        return self::tryParse($value) instanceof self;
    }

    /**
     * The stored form: +237650000001.
     */
    public function toE164(): string
    {
        return '+'.self::COUNTRY_CODE.$this->national;
    }

    /**
     * The readable form: 650 00 00 01.
     */
    public function format(): string
    {
        return implode(' ', [
            substr($this->national, 0, 3),
            substr($this->national, 3, 2),
            substr($this->national, 5, 2),
            substr($this->national, 7, 2),
        ]);
    }

    /**
     * The readable form with the country code: +237 650 00 00 01.
     */
    public function formatInternational(): string
    {
        return '+'.self::COUNTRY_CODE.' '.$this->format();
    }

    public function isMobile(): bool
    {
        return str_starts_with($this->national, '6');
    }

    public function equals(self $other): bool
    {
        return $this->national === $other->national;
    }

    public function __toString(): string
    {
        return $this->toE164();
    }
}
