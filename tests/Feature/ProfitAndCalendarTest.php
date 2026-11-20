<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\FinancialAccount;
use App\Models\Transaction;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfitAndCalendarTest extends TestCase
{
    use RefreshDatabase;

    private Workspace $workspace;

    private FinancialAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-11-20 12:00:00');
        $this->workspace = Workspace::factory()->business()->create();
        $this->account = FinancialAccount::factory()->for($this->workspace)->create(['opening_balance' => '100.00']);
        Sanctum::actingAs($this->workspace->owner);
    }

    public function test_an_expense_with_money_earned_back_shows_its_profit(): void
    {
        $stock = Category::factory()->for($this->workspace)->create(['name' => 'Stock']);

        $this->postJson($this->url('transactions'), [
            'type' => 'expense',
            'account_id' => $this->account->id,
            'category_id' => $stock->id,
            'amount' => '300.00',
            'return_amount' => '450.00',
            'transaction_date' => '2026-11-03',
        ])->assertCreated()
            ->assertJsonPath('data.return_amount', '450.0000')
            ->assertJsonPath('data.profit', '150.00');
    }

    public function test_a_loss_is_negative(): void
    {
        $this->postJson($this->url('transactions'), [
            'type' => 'expense', 'account_id' => $this->account->id,
            'amount' => '80', 'return_amount' => '50', 'transaction_date' => '2026-11-03',
        ])->assertJsonPath('data.profit', '-30.00');
    }

    public function test_only_expenses_can_have_money_earned_back(): void
    {
        $this->postJson($this->url('transactions'), [
            'type' => 'income', 'account_id' => $this->account->id,
            'amount' => '80', 'return_amount' => '10', 'transaction_date' => '2026-11-03',
        ])->assertJsonValidationErrors(['return_amount' => 'Only expenses can have money earned back.']);
    }

    public function test_returns_count_in_the_net_result_and_the_account_balance(): void
    {
        $this->ledger([
            ['income', '1000.00', null, '2026-11-02'],
            ['expense', '300.00', '450.00', '2026-11-03'],
            ['expense', '100.00', null, '2026-11-03'],
        ]);

        $this->getJson($this->url('dashboard').'?month=2026-11')
            ->assertJsonPath('data.totals', ['income' => '1000.00', 'expenses' => '400.00', 'returns' => '450.00', 'net' => '1050.00'])
            ->assertJsonPath('data.accounts.0.balance', '1150.00'); // 100 + 1000 - 300 + 450 - 100
    }

    public function test_the_calendar_gives_profit_or_loss_per_day(): void
    {
        $this->ledger([
            ['income', '200.00', null, '2026-11-02'],
            ['expense', '50.00', null, '2026-11-02'],
            ['expense', '300.00', '250.00', '2026-11-05'],
            ['expense', '10.00', null, '2026-10-31'],
        ]);

        $response = $this->getJson($this->url('calendar').'?month=2026-11')->assertOk();

        $response->assertJsonPath('data.month', '2026-11')
            ->assertJsonPath('data.today', '2026-11-20')
            ->assertJsonCount(2, 'data.days')
            ->assertJsonPath('data.days.0', ['date' => '2026-11-02', 'income' => '200.00', 'expenses' => '50.00', 'returns' => '0.00', 'net' => '150.00', 'count' => 2])
            ->assertJsonPath('data.days.1.net', '-50.00')
            ->assertJsonPath('data.totals.net', '100.00');
    }

    public function test_the_calendar_of_another_workspace_is_not_found(): void
    {
        $other = Workspace::factory()->create();

        $this->getJson("/api/v1/workspaces/{$other->id}/calendar")->assertNotFound();
    }

    /**
     * @param  list<array{0: string, 1: string, 2: string|null, 3: string}>  $rows
     */
    private function ledger(array $rows): void
    {
        foreach ($rows as [$type, $amount, $return, $date]) {
            Transaction::factory()->for($this->workspace)->create([
                'account_id' => $this->account->id, 'type' => $type, 'amount' => $amount,
                'return_amount' => $return, 'transaction_date' => $date,
            ]);
        }
    }

    private function url(string $path): string
    {
        return "/api/v1/workspaces/{$this->workspace->id}/{$path}";
    }
}
