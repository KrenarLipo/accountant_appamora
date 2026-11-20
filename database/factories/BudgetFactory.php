<?php

namespace Database\Factories;

use App\Models\Budget;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Budget>
 */
class BudgetFactory extends Factory
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
            'category_id' => null,
            'amount' => '1000.0000',
            'alert_thresholds' => [70, 90, 100],
        ];
    }
}
