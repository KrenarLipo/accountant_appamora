<?php

namespace Database\Factories;

use App\Enums\WorkspaceRole;
use App\Enums\WorkspaceType;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Workspace>
 */
class WorkspaceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'type' => WorkspaceType::Personal,
            'base_currency' => 'EUR',
            'timezone' => 'Europe/Tirane',
            'country' => 'AL',
            'owner_id' => User::factory(),
        ];
    }

    public function business(): static
    {
        return $this->state(fn (array $attributes) => ['type' => WorkspaceType::Business]);
    }

    /**
     * Attach the owner as a member with the Owner role.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Workspace $workspace): void {
            $workspace->members()->syncWithoutDetaching([
                $workspace->owner_id => ['role' => WorkspaceRole::Owner->value],
            ]);
        });
    }
}
