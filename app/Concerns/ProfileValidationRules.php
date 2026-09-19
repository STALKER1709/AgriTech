<?php

namespace App\Concerns;

use App\Models\User;
use App\Rules\CameroonPhoneNumber;
use App\Rules\UniquePhoneNumber;
use App\Support\CameroonRegions;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Stringable;

trait ProfileValidationRules
{
    /**
     * Get the validation rules used to validate user profiles.
     *
     * @return array<string, array<int, ValidationRule|Stringable|array<mixed>|string>>
     */
    protected function profileRules(?int $userId = null): array
    {
        return [
            'first_name' => $this->nameRules(),
            'last_name' => $this->nameRules(),
            'email' => $this->emailRules($userId),
            'phone' => $this->phoneRules($userId),
        ];
    }

    /**
     * Get the validation rules used to validate user names.
     *
     * @return array<int, ValidationRule|Stringable|array<mixed>|string>
     */
    protected function nameRules(): array
    {
        return ['required', 'string', 'max:255'];
    }

    /**
     * Get the validation rules used to validate user emails.
     *
     * @return array<int, ValidationRule|Stringable|array<mixed>|string>
     */
    protected function emailRules(?int $userId = null): array
    {
        return [
            'required',
            'string',
            'email',
            'max:255',
            $userId === null
                ? Rule::unique(User::class)
                : Rule::unique(User::class)->ignore($userId),
        ];
    }

    /**
     * Get the validation rules used to validate Cameroonian phone numbers.
     *
     * Shape and uniqueness are two separate rules: the second compares the
     * normalised number, so the same line written two ways still collides.
     *
     * @return array<int, ValidationRule|Stringable|array<mixed>|string>
     */
    protected function phoneRules(?int $userId = null): array
    {
        return [
            'required',
            'string',
            'max:20',
            new CameroonPhoneNumber,
            new UniquePhoneNumber($userId),
        ];
    }

    /**
     * Get the validation rules used to validate a farm profile.
     *
     * @return array<string, array<int, ValidationRule|Stringable|array<mixed>|string>>
     */
    protected function farmRules(): array
    {
        return [
            'farm_name' => ['required', 'string', 'max:255'],
            'region' => ['required', 'string', Rule::in(CameroonRegions::all())],
            'city' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
