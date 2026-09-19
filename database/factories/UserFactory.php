<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Throwable;

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
     *
     * Null until it has been seeded from what is already stored: the counter
     * lives in the process, the numbers live in the database, and a script run
     * twice against the same database would otherwise reuse the first run's
     * numbers.
     */
    private static ?int $phoneSequence = null;

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
     *
     * Generated numbers sit in their own band, +23761…, which no seeded demo
     * account uses. Without that separation a factory user can collide with a
     * seeded one on the unique phone column — as one happily did.
     */
    public static function nextPhoneNumber(): string
    {
        self::$phoneSequence ??= self::highestGeneratedNumber();
        self::$phoneSequence++;

        return sprintf('+23761%07d', self::$phoneSequence % 10_000_000);
    }

    /**
     * The highest number already handed out in the factory band.
     *
     * Read once per process. A missing table simply means nothing has been
     * handed out yet, which is the case when a factory runs before migrations.
     */
    private static function highestGeneratedNumber(): int
    {
        try {
            $highest = User::query()
                ->where('phone', 'like', '+23761%')
                ->max('phone');
        } catch (Throwable) {
            return 0;
        }

        return is_string($highest) ? (int) substr($highest, -7) : 0;
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
