<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SettingType;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Setting>
 */
class SettingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => fake()->unique()->slug(2),
            'value' => (string) fake()->numberBetween(1, 100),
            'type' => SettingType::Integer,
            'label' => fake()->sentence(3),
            'description' => null,
        ];
    }

    public function boolean(bool $value): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => SettingType::Boolean,
            'value' => $value ? '1' : '0',
        ]);
    }

    public function integer(int $value): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => SettingType::Integer,
            'value' => (string) $value,
        ]);
    }
}
