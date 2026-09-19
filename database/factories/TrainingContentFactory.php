<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TrainingContentType;
use App\Models\Training;
use App\Models\TrainingContent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingContent>
 */
class TrainingContentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(TrainingContentType::cases());

        return [
            'training_id' => Training::factory(),
            'title' => rtrim(fake()->sentence(3), '.'),
            'type' => $type,
            'path' => 'trainings/'.fake()->uuid().($type === TrainingContentType::Video ? '.mp4' : '.pdf'),
            'position' => 0,
        ];
    }

    public function video(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => TrainingContentType::Video,
            'path' => 'trainings/'.fake()->uuid().'.mp4',
        ]);
    }

    public function pdf(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => TrainingContentType::Pdf,
            'path' => 'trainings/'.fake()->uuid().'.pdf',
        ]);
    }
}
