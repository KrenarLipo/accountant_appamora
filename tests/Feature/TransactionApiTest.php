<?php

namespace Tests\Feature;

use App\Enums\WorkspaceRole;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\FinancialAccount;
use App\Models\Transaction;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TransactionApiTest extends TestCase
{
    use RefreshDatabase;

    private Workspace $workspace;

    private FinancialAccount $account;

    private Category $groceries;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workspace = Workspace::factory()->create();
        $this->account = FinancialAccount::factory()->for($this->workspace)->create();
        $this->groceries = Category::factory()->for($this->workspace)->create(['name' => 'Groceries']);
    }

    public function test_a_member_can_record_an_expense_and_the_change_is_audited(): void
    {
        Sanctum::actingAs($this->workspace->owner);

        $response = $this->postJson($this->url(), [
            'type' => 'expense',
            'account_id' => $this->account->id,
            'category_id' => $this->groceries->id,
            'amount' => '42.10',
            'transaction_date' => '2026-11-10',
            'merchant' => 'Conad',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.amount', '42.1000')
            ->assertJsonPath('data.currency', 'EUR')
            ->assertJsonPath('data.category.name', 'Groceries');

        $transaction = Transaction::sole();
        $this->assertSame($this->workspace->owner->id, $transaction->created_by);

        $log = AuditLog::where('auditable_type', Transaction::class)->sole();
        $this->assertSame('created', $log->action);
        $this->assertSame($this->workspace->owner->id, $log->actor_id);
        $this->assertSame($this->workspace->id, $log->workspace_id);
    }

    public function test_a_contributor_can_record_transactions_but_a_viewer_cannot(): void
    {
        $payload = $this->expensePayload();

        Sanctum::actingAs($this->memberOf($this->workspace, WorkspaceRole::Contributor));
        $this->postJson($this->url(), $payload)->assertCreated();

        Sanctum::actingAs($this->memberOf($this->workspace, WorkspaceRole::Viewer));
        $this->postJson($this->url(), $payload)->assertForbidden();

        $this->assertSame(1, Transaction::count());
    }

    public function test_accounts_and_categories_from_another_workspace_are_rejected(): void
    {
        $otherAccount = FinancialAccount::factory()->create();
        $otherCategory = Category::factory()->create();
        Sanctum::actingAs($this->workspace->owner);

        $this->postJson($this->url(), [
            ...$this->expensePayload(),
            'account_id' => $otherAccount->id,
            'category_id' => $otherCategory->id,
        ])->assertUnprocessable()->assertJsonValidationErrors(['account_id', 'category_id']);

        $this->assertSame(0, Transaction::count());
    }

    public function test_an_expense_cannot_use_an_income_category(): void
    {
        $salary = Category::factory()->for($this->workspace)->income()->create();
        Sanctum::actingAs($this->workspace->owner);

        $this->postJson($this->url(), [...$this->expensePayload(), 'category_id' => $salary->id])
            ->assertJsonValidationErrors(['category_id' => 'Choose a category that matches the transaction type.']);
    }

    public function test_amounts_must_be_positive_with_at_most_two_decimals(): void
    {
        Sanctum::actingAs($this->workspace->owner);

        $this->postJson($this->url(), [...$this->expensePayload(), 'amount' => '-5'])->assertJsonValidationErrors('amount');
        $this->postJson($this->url(), [...$this->expensePayload(), 'amount' => '1.999'])->assertJsonValidationErrors('amount');
    }

    public function test_a_transfer_needs_a_different_destination_account_and_no_category(): void
    {
        Sanctum::actingAs($this->workspace->owner);
        $payload = ['type' => 'transfer', 'account_id' => $this->account->id, 'amount' => '10', 'transaction_date' => '2026-11-10'];

        $this->postJson($this->url(), $payload)
            ->assertJsonValidationErrors(['transfer_account_id' => 'Choose the account to transfer to.']);

        $this->postJson($this->url(), [...$payload, 'transfer_account_id' => $this->account->id])
            ->assertJsonValidationErrors(['transfer_account_id' => 'Choose a different account to transfer to.']);

        $savings = FinancialAccount::factory()->for($this->workspace)->create();
        $this->postJson($this->url(), [...$payload, 'transfer_account_id' => $savings->id, 'category_id' => $this->groceries->id])
            ->assertJsonValidationErrors('category_id');

        $this->postJson($this->url(), [...$payload, 'transfer_account_id' => $savings->id])->assertCreated();
    }

    public function test_a_transaction_from_another_workspace_cannot_be_read_or_changed_through_this_workspace(): void
    {
        $foreign = Transaction::factory()->create();
        Sanctum::actingAs($this->workspace->owner);

        $this->getJson($this->url($foreign->id))->assertNotFound();
        $this->deleteJson($this->url($foreign->id))->assertNotFound();
        $this->putJson($this->url($foreign->id), $this->expensePayload())->assertNotFound();

        $this->assertNotSoftDeleted($foreign);
    }

    public function test_listing_filters_by_type_and_search_and_newest_first(): void
    {
        Transaction::factory()->for($this->workspace)->create(['account_id' => $this->account->id, 'merchant' => 'Old shop', 'transaction_date' => '2026-10-01']);
        Transaction::factory()->for($this->workspace)->create(['account_id' => $this->account->id, 'merchant' => 'New shop', 'transaction_date' => '2026-11-01']);
        Transaction::factory()->for($this->workspace)->create(['account_id' => $this->account->id, 'merchant' => 'Employer', 'type' => 'income']);
        Sanctum::actingAs($this->workspace->owner);

        $this->getJson($this->url().'?type=expense&search=shop')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.merchant', 'New shop')
            ->assertJsonPath('data.1.merchant', 'Old shop');
    }

    public function test_updating_and_deleting_a_transaction(): void
    {
        $transaction = Transaction::factory()->for($this->workspace)->create(['account_id' => $this->account->id, 'amount' => '10']);
        Sanctum::actingAs($this->workspace->owner);

        $this->putJson($this->url($transaction->id), [...$this->expensePayload(), 'amount' => '25.00'])
            ->assertOk()
            ->assertJsonPath('data.amount', '25.0000');
        $this->assertSame($this->workspace->owner->id, $transaction->fresh()->updated_by);

        $this->deleteJson($this->url($transaction->id))->assertNoContent();
        $this->assertSoftDeleted($transaction);
        $this->assertDatabaseHas('audit_logs', ['auditable_id' => $transaction->id, 'action' => 'deleted']);
    }

    /**
     * @return array<string, mixed>
     */
    private function expensePayload(): array
    {
        return [
            'type' => 'expense',
            'account_id' => $this->account->id,
            'category_id' => $this->groceries->id,
            'amount' => '12.00',
            'transaction_date' => '2026-11-10',
        ];
    }

    private function url(?int $transactionId = null): string
    {
        return "/api/v1/workspaces/{$this->workspace->id}/transactions".($transactionId ? "/{$transactionId}" : '');
    }
}
