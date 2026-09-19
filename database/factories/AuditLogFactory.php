<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'actor_id' => User::factory()->admin(),
            'action' => fake()->randomElement([
                'farmer.approved',
                'farmer.rejected',
                'user.suspended',
                'publication.moderated',
                'setting.updated',
            ]),
            'auditable_type' => null,
            'auditable_id' => null,
            'before' => null,
            'after' => null,
        ];
    }

    public function on(Model $auditable): static
    {
        return $this->state(fn (array $attributes): array => [
            'auditable_type' => $auditable->getMorphClass(),
            'auditable_id' => $auditable->getKey(),
        ]);
    }
}
