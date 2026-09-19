<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\User;
use App\Support\PhoneNumber;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Checks phone number uniqueness against the normalised form.
 *
 * A plain unique rule would compare what was typed, so "650 00 00 01" would
 * slip past an existing "+237650000001" and only blow up later on the database
 * constraint. Normalising first is what makes the check mean anything.
 */
final class UniquePhoneNumber implements ValidationRule
{
    public function __construct(private readonly ?int $ignoreUserId = null) {}

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $number = PhoneNumber::tryParse($value);

        if (! $number instanceof PhoneNumber) {
            // Shape is CameroonPhoneNumber's business; nothing to add here.
            return;
        }

        $exists = User::query()
            ->where('phone', $number->toE164())
            ->when($this->ignoreUserId !== null, fn ($query) => $query->whereKeyNot($this->ignoreUserId))
            ->exists();

        if ($exists) {
            $fail('validation.unique')->translate();
        }
    }
}
