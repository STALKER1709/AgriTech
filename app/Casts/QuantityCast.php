<?php

declare(strict_types=1);

namespace App\Casts;

use App\Support\Quantity;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Cast a decimal(12,3) column to and from the Quantity value object.
 *
 * Floats are refused outright: accepting one here is how a rounding error
 * would reach the stock figures business rule RG04 depends on.
 *
 * @implements CastsAttributes<Quantity, mixed>
 */
final class QuantityCast implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Quantity
    {
        if ($value === null) {
            return null;
        }

        return Quantity::fromString((string) $value);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Quantity) {
            return $value->toDecimalString();
        }

        if (is_int($value)) {
            return Quantity::fromInteger($value)->toDecimalString();
        }

        if (is_string($value)) {
            return Quantity::fromString($value)->toDecimalString();
        }

        throw new InvalidArgumentException(sprintf(
            'The [%s] attribute holds a quantity and only accepts an integer, a decimal string or a %s instance, %s given.',
            $key,
            Quantity::class,
            get_debug_type($value),
        ));
    }
}
