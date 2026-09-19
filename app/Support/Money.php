<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * An amount of money in CFA francs.
 *
 * Business rule RG10: the XAF has no subdivision in practical use, so every
 * amount is held as a plain integer of francs. Nothing in this class ever
 * produces or accepts a float, which is what keeps rounding drift out of the
 * accounting entirely.
 *
 * @immutable
 */
final readonly class Money implements JsonSerializable, Stringable
{
    public const string CURRENCY = 'XAF';

    /**
     * Narrow no-break space (U+202F), the separator French typography uses
     * for thousands.
     */
    private const string THOUSANDS_SEPARATOR = "\u{202F}";

    /**
     * No-break space (U+00A0) between the amount and the currency, so a line
     * break never separates "12 500" from "FCFA".
     */
    private const string CURRENCY_SEPARATOR = "\u{00A0}";

    private function __construct(public int $amount) {}

    public static function fromInteger(int $amount): self
    {
        return new self($amount);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public function plus(self $other): self
    {
        return new self($this->amount + $other->amount);
    }

    public function minus(self $other): self
    {
        return new self($this->amount - $other->amount);
    }

    /**
     * Multiply by a whole factor, typically a quantity of items.
     */
    public function multipliedBy(int $factor): self
    {
        return new self($this->amount * $factor);
    }

    /**
     * Take a percentage of this amount, rounding half up to the nearest franc.
     *
     * The percentage is itself an integer: a 5% commission is percentage(5).
     * Rounding is explicit rather than left to a float division, so the result
     * is reproducible.
     */
    public function percentage(int $percent): self
    {
        if ($percent < 0) {
            throw new InvalidArgumentException('A percentage cannot be negative.');
        }

        return new self(intdiv($this->amount * $percent * 2 + 100, 200));
    }

    /**
     * Multiply by a fractional quantity, rounding half up to the nearest franc.
     *
     * This is how an order line total is computed: 1 000 FCFA per kilogram
     * times 12.5 kg gives exactly 12 500 FCFA, with no float in the path.
     */
    public function multipliedByQuantity(Quantity $quantity): self
    {
        $numerator = $this->amount * $quantity->thousandths;

        return new self(intdiv($numerator * 2 + 1000, 2000));
    }

    public function isZero(): bool
    {
        return $this->amount === 0;
    }

    public function isPositive(): bool
    {
        return $this->amount > 0;
    }

    public function isNegative(): bool
    {
        return $this->amount < 0;
    }

    public function equals(self $other): bool
    {
        return $this->amount === $other->amount;
    }

    public function isGreaterThan(self $other): bool
    {
        return $this->amount > $other->amount;
    }

    public function isLessThan(self $other): bool
    {
        return $this->amount < $other->amount;
    }

    /**
     * Sum a list of amounts.
     *
     * @param  iterable<int, self>  $amounts
     */
    public static function sum(iterable $amounts): self
    {
        $total = 0;

        foreach ($amounts as $money) {
            $total += $money->amount;
        }

        return new self($total);
    }

    /**
     * Render the amount the way it is displayed to users: "12 500 FCFA".
     */
    public function format(): string
    {
        return $this->formatNumber().self::CURRENCY_SEPARATOR.'FCFA';
    }

    /**
     * Render the grouped digits without the currency, for table columns that
     * already carry the unit in their header.
     */
    public function formatNumber(): string
    {
        $sign = $this->amount < 0 ? '-' : '';
        $digits = (string) abs($this->amount);

        // Grouped from the right. Reversing the joined string instead would
        // scramble the separator, which is a multi-byte UTF-8 character.
        $groups = [];

        while (strlen($digits) > 3) {
            array_unshift($groups, substr($digits, -3));
            $digits = substr($digits, 0, -3);
        }

        array_unshift($groups, $digits);

        return $sign.implode(self::THOUSANDS_SEPARATOR, $groups);
    }

    public function __toString(): string
    {
        return $this->format();
    }

    public function jsonSerialize(): int
    {
        return $this->amount;
    }
}
