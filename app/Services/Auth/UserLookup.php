<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;
use App\Support\PhoneNumber;

/**
 * Resolves the single login field to an account.
 *
 * A visitor may type an email address or a phone number, in any of the shapes
 * people actually use. Which one it is is decided here rather than by the form.
 */
final class UserLookup
{
    public function findByLogin(string $login): ?User
    {
        $login = trim($login);

        if ($login === '') {
            return null;
        }

        $phone = PhoneNumber::tryParse($login);

        if ($phone instanceof PhoneNumber) {
            return User::query()->where('phone', $phone->toE164())->first();
        }

        return User::query()->where('email', mb_strtolower($login))->first();
    }
}
