<?php

namespace Database\Factories;

use App\Enums\AccountType;
use App\Models\FinancialAccount;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinancialAccount>
 */
class FinancialAccountFactory extends Factory
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
            'name' => fake()->randomElement(['Main bank', 'Cash', 'Savings']),
            'type' => AccountType::Bank,
            'currency' => 'EUR',
            'opening_balance' => '0.0000',
            'is_active' => true,
        ];
    }
}
