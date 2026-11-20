<?php

namespace Database\Factories;

use App\Enums\TransactionType;
use App\Models\FinancialAccount;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'account_id' => FinancialAccount::factory(),
            'workspace_id' => fn (array $attributes) => FinancialAccount::find($attributes['account_id'])->workspace_id,
            'type' => TransactionType::Expense,
            'amount' => fake()->randomFloat(2, 1, 200),
            'currency' => 'EUR',
            'transaction_date' => now()->toDateString(),
            'merchant' => fake()->company(),
        ];
    }
}
