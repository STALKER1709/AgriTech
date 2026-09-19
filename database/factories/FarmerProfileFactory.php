<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\FarmerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FarmerProfile>
 */
class FarmerProfileFactory extends Factory
{
    /**
     * Regions and towns of Cameroon, so that demo data reads as plausible.
     *
     * @var array<string, array<int, string>>
     */
    private const array PLACES = [
        'Centre' => ['Yaoundé', 'Mbalmayo', 'Obala'],
        'Littoral' => ['Douala', 'Nkongsamba', 'Edéa'],
        'Ouest' => ['Bafoussam', 'Dschang', 'Foumban'],
        'Nord-Ouest' => ['Bamenda', 'Kumbo'],
        'Sud-Ouest' => ['Buéa', 'Limbé'],
        'Adamaoua' => ['Ngaoundéré', 'Meiganga'],
        'Est' => ['Bertoua', 'Batouri'],
        'Extrême-Nord' => ['Maroua', 'Kousséri'],
        'Nord' => ['Garoua', 'Guider'],
        'Sud' => ['Ebolowa', 'Kribi'],
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $region = fake()->randomElement(array_keys(self::PLACES));

        return [
            'user_id' => User::factory()->farmer(),
            'farm_name' => 'Ferme '.fake()->lastName(),
            'region' => $region,
            'city' => fake()->randomElement(self::PLACES[$region]),
            'description' => fake()->sentence(12),
            'validated_at' => null,
            'validated_by' => null,
            'rejection_reason' => null,
        ];
    }

    public function validated(?User $validator = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'validated_at' => now(),
            'validated_by' => $validator instanceof User ? $validator->id : User::factory()->admin(),
        ]);
    }

    public function rejected(string $reason = 'Justificatifs incomplets.'): static
    {
        return $this->state(fn (array $attributes): array => [
            'validated_at' => null,
            'validated_by' => User::factory()->admin(),
            'rejection_reason' => $reason,
        ]);
    }
}
