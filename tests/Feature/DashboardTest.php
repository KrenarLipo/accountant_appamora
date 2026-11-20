<?php

namespace Tests\Feature;

use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\FinancialAccount;
use App\Models\Transaction;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A small reference ledger: totals must reconcile exactly and transfers must not count as income or expenses.
     */
    public function test_dashboard_totals_and_balances_reconcile_with_the_ledger(): void
    {
        $workspace = Workspace::factory()->create();
        $bank = FinancialAccount::factory()->for($workspace)->create(['name' => 'Bank', 'opening_balance' => '1000.00']);
        $cash = FinancialAccount::factory()->for($workspace)->create(['name' => 'Cash', 'opening_balance' => '50.00']);
        $food = Category::factory()->for($workspace)->create(['name' => 'Food']);
        $rent = Category::factory()->for($workspace)->create(['name' => 'Rent']);

        $ledger = [
            [TransactionType::Income, $bank, null, null, '2000.00', '2026-11-01', 'Employer'],
            [TransactionType::Expense, $bank, null, $rent, '700.00', '2026-11-02', 'Landlord'],
            [TransactionType::Expense, $cash, null, $food, '0.10', '2026-11-03', 'Bakery'],
            [TransactionType::Expense, $cash, null, $food, '0.20', '2026-11-04', 'Bakery'],
            [TransactionType::Transfer, $bank, $cash, null, '100.00', '2026-11-05', null],
            [TransactionType::Expense, $bank, null, $food, '30.00', '2026-10-15', 'Market'],
        ];

        foreach ($ledger as [$type, $account, $to, $category, $amount, $date, $merchant]) {
            Transaction::factory()->for($workspace)->create([
                'type' => $type,
                'account_id' => $account->id,
                'transfer_account_id' => $to?->id,
                'category_id' => $category?->id,
                'amount' => $amount,
                'transaction_date' => $date,
                'merchant' => $merchant,
            ]);
        }

        Sanctum::actingAs($workspace->owner);

        $response = $this->getJson("/api/v1/workspaces/{$workspace->id}/dashboard?month=2026-11")->assertOk();

        $response->assertJsonPath('data.totals', ['income' => '2000.00', 'expenses' => '700.30', 'returns' => '0.00', 'net' => '1299.70'])
            ->assertJsonPath('data.previous_totals', ['income' => '0.00', 'expenses' => '30.00', 'returns' => '0.00', 'net' => '-30.00'])
            ->assertJsonPath('data.currency', 'EUR')
            ->assertJsonPath('data.expenses_by_category.0.name', 'Rent')
            ->assertJsonPath('data.expenses_by_category.1.total', '0.30')
            ->assertJsonPath('data.top_merchants.0.merchant', 'Landlord');

        $balances = collect($response->json('data.accounts'))->pluck('balance', 'name');
        $this->assertSame('2170.00', $balances['Bank']); // 1000 + 2000 - 700 - 100 - 30
        $this->assertSame('149.70', $balances['Cash']);  // 50 - 0.30 + 100

        $november = collect($response->json('data.trend'))->firstWhere('month', '2026-11');
        $this->assertSame(['month' => '2026-11', 'income' => '2000.00', 'expenses' => '700.30', 'returns' => '0.00', 'net' => '1299.70'], $november);
    }

    public function test_deleted_transactions_are_excluded_from_totals(): void
    {
        $workspace = Workspace::factory()->create();
        $account = FinancialAccount::factory()->for($workspace)->create();
        Transaction::factory()->for($workspace)->create(['account_id' => $account->id, 'amount' => '40', 'transaction_date' => '2026-11-02'])->delete();
        Sanctum::actingAs($workspace->owner);

        $this->getJson("/api/v1/workspaces/{$workspace->id}/dashboard?month=2026-11")
            ->assertJsonPath('data.totals.expenses', '0.00')
            ->assertJsonPath('data.expenses_by_category', []);
    }
}
