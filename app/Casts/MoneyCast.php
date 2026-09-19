<?php

declare(strict_types=1);

namespace App\Casts;

use App\Support\Money;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Cast an integer column of CFA francs to and from the Money value object.
 *
 * Assigning anything other than an integer or a Money instance is rejected on
 * the spot, which is how business rule RG10 is enforced at the model boundary
 * rather than hoped for.
 *
 * @implements CastsAttributes<Money, mixed>
 */
final class MoneyCast implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Money
    {
        if ($value === null) {
            return null;
        }

        return Money::fromInteger((int) $value);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?int
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Money) {
            return $value->amount;
        }

        if (is_int($value)) {
            return $value;
        }

        throw new InvalidArgumentException(sprintf(
            'The [%s] attribute holds money and only accepts an integer or a %s instance, %s given.',
            $key,
            Money::class,
            get_debug_type($value),
        ));
    }
}
