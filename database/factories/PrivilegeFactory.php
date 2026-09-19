<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Privilege;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Privilege>
 */
class PrivilegeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->slug(2).'.'.fake()->word(),
            'label' => fake()->sentence(3),
        ];
    }

    public function withCode(string $code): static
    {
        return $this->state(fn (array $attributes): array => [
            'code' => $code,
            'label' => Privilege::catalogue()[$code] ?? $code,
        ]);
    }
}
