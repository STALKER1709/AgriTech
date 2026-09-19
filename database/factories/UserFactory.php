<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * A running counter, so that generated phone numbers stay unique without
     * relying on the faker unique() pool, which gives up after a while.
     */
    private static int $phoneSequence = 0;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => self::nextPhoneNumber(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => UserRole::Client,
            'status' => UserStatus::Active,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * A Cameroonian mobile number in its normalised international form.
     */
    public static function nextPhoneNumber(): string
    {
        self::$phoneSequence++;

        return sprintf('+2376%08d', self::$phoneSequence % 100_000_000);
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'email_verified_at' => null,
        ]);
    }

    public function client(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => UserRole::Client,
            'status' => UserStatus::Active,
        ]);
    }

    public function farmer(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => UserRole::Farmer,
            'status' => UserStatus::Active,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => UserRole::Admin,
            'status' => UserStatus::Active,
        ]);
    }

    /**
     * A farmer who signed up but has not paid the registration fee yet.
     */
    public function awaitingPayment(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => UserRole::Farmer,
            'status' => UserStatus::PendingPayment,
        ]);
    }

    /**
     * A farmer whose fee is paid and who is waiting on an administrator.
     */
    public function awaitingValidation(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => UserRole::Farmer,
            'status' => UserStatus::PendingValidation,
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => UserStatus::Suspended,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => UserRole::Farmer,
            'status' => UserStatus::Rejected,
        ]);
    }
}
