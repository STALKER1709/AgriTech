<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * A quantity of goods, held to three decimal places.
 *
 * Unlike money, quantities are genuinely fractional — 12.500 kg of tomatoes is
 * an ordinary order line. They are still stored as an integer, here a count of
 * thousandths, for the same reason money is: a float comparison is exactly
 * what would let business rule RG04 leak a negative stock.
 *
 * @immutable
 */
final readonly class Quantity implements JsonSerializable, Stringable
{
    public const int SCALE = 3;

    private const int FACTOR = 1000;

    private function __construct(public int $thousandths) {}

    public static function fromThousandths(int $thousandths): self
    {
        return new self($thousandths);
    }

    public static function fromInteger(int $units): self
    {
        return new self($units * self::FACTOR);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    /**
     * Parse a decimal string such as "12.5", "12.500" or "-3".
     *
     * Anything with more than three decimals, or that is not a decimal number
     * at all, is rejected rather than silently truncated.
     */
    public static function fromString(string $value): self
    {
        $value = trim($value);

        if (preg_match('/^(-?)(\d+)(?:\.(\d{1,3}))?$/', $value, $matches) !== 1) {
            throw new InvalidArgumentException(
                sprintf('[%s] is not a quantity with at most %d decimals.', $value, self::SCALE),
            );
        }

        $decimals = str_pad($matches[3] ?? '', self::SCALE, '0');
        $thousandths = (int) $matches[2] * self::FACTOR + (int) $decimals;

        return new self($matches[1] === '-' ? -$thousandths : $thousandths);
    }

    public function plus(self $other): self
    {
        return new self($this->thousandths + $other->thousandths);
    }

    public function minus(self $other): self
    {
        return new self($this->thousandths - $other->thousandths);
    }

    public function isZero(): bool
    {
        return $this->thousandths === 0;
    }

    public function isPositive(): bool
    {
        return $this->thousandths > 0;
    }

    public function isNegative(): bool
    {
        return $this->thousandths < 0;
    }

    public function equals(self $other): bool
    {
        return $this->thousandths === $other->thousandths;
    }

    public function isGreaterThan(self $other): bool
    {
        return $this->thousandths > $other->thousandths;
    }

    public function isGreaterThanOrEqualTo(self $other): bool
    {
        return $this->thousandths >= $other->thousandths;
    }

    public function isLessThan(self $other): bool
    {
        return $this->thousandths < $other->thousandths;
    }

    /**
     * The canonical decimal string, matching the decimal(12,3) column.
     */
    public function toDecimalString(): string
    {
        $sign = $this->thousandths < 0 ? '-' : '';
        $absolute = abs($this->thousandths);

        return sprintf('%s%d.%03d', $sign, intdiv($absolute, self::FACTOR), $absolute % self::FACTOR);
    }

    /**
     * The display form, with trailing zeros dropped: "12,5" rather than
     * "12.500". French notation uses a comma for the decimal mark.
     */
    public function format(): string
    {
        $plain = rtrim(rtrim($this->toDecimalString(), '0'), '.');

        return str_replace('.', ',', $plain);
    }

    public function __toString(): string
    {
        return $this->toDecimalString();
    }

    public function jsonSerialize(): string
    {
        return $this->toDecimalString();
    }
}
