<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Conversation>
 */
class ConversationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => User::factory()->client(),
            'farmer_id' => User::factory()->farmer(),
            'last_message_at' => null,
        ];
    }

    public function between(User $client, User $farmer): static
    {
        return $this->state(fn (array $attributes): array => [
            'client_id' => $client->id,
            'farmer_id' => $farmer->id,
        ]);
    }
}
