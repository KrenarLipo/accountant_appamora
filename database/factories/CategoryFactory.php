<?php

namespace Database\Factories;

use App\Enums\CategoryType;
use App\Models\Category;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'name' => ucfirst(fake()->unique()->word()),
            'type' => CategoryType::Expense,
            'color' => fake()->hexColor(),
        ];
    }

    public function income(): static
    {
        return $this->state(fn (array $attributes) => ['type' => CategoryType::Income]);
    }
}
