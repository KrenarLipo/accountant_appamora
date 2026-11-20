<?php

namespace Tests\Feature;

use App\Enums\WorkspaceRole;
use App\Models\Budget;
use App\Models\Category;
use App\Models\FinancialAccount;
use App\Models\Workspace;
use App\Notifications\BudgetThresholdReached;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BudgetAlertTest extends TestCase
{
    use RefreshDatabase;

    private Workspace $workspace;

    private FinancialAccount $account;

    private Category $dining;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-11-20 12:00:00');
        $this->workspace = Workspace::factory()->create();
        $this->account = FinancialAccount::factory()->for($this->workspace)->create();
        $this->dining = Category::factory()->for($this->workspace)->create(['name' => 'Dining out']);
        Budget::factory()->for($this->workspace)->create([
            'category_id' => $this->dining->id,
            'amount' => '250.00',
            'alert_thresholds' => [70, 90, 100],
        ]);
    }

    public function test_crossing_a_threshold_sends_one_alert_to_finance_members_only(): void
    {
        Notification::fake();
        $viewer = $this->memberOf($this->workspace, WorkspaceRole::Viewer);
        $manager = $this->memberOf($this->workspace, WorkspaceRole::FinanceManager);
        Sanctum::actingAs($this->workspace->owner);

        $this->spend('230.00'); // 92%: crosses 70 and 90 at once → one alert for 90

        Notification::assertSentTo([$this->workspace->owner, $manager], BudgetThresholdReached::class, function ($notification) {
            return $notification->threshold === 90
                && $notification->toArray($this->workspace->owner)['title'] === 'Dining out has reached 90% of its budget';
        });
        Notification::assertNotSentTo($viewer, BudgetThresholdReached::class);
        $this->assertDatabaseCount('budget_alerts', 2);
    }

    public function test_the_same_threshold_is_not_alerted_twice_in_a_month(): void
    {
        Notification::fake();
        Sanctum::actingAs($this->workspace->owner);

        $this->spend('180.00'); // 72%
        $this->spend('5.00');   // 74%, still only past 70

        Notification::assertSentToTimes($this->workspace->owner, BudgetThresholdReached::class, 1);

        $this->spend('80.00');  // 106%

        Notification::assertSentToTimes($this->workspace->owner, BudgetThresholdReached::class, 2);
    }

    public function test_alerts_are_stored_in_the_notification_centre(): void
    {
        Sanctum::actingAs($this->workspace->owner);

        $this->spend('260.00');

        $this->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonPath('data.0.data.title', 'Dining out budget is used up')
            ->assertJsonPath('data.0.data.link', '/app/budgets');
        $this->getJson('/api/v1/me')->assertJsonPath('data.unread_notifications', 1);

        $this->deleteJson('/api/v1/notifications')->assertNoContent();
        $this->getJson('/api/v1/me')->assertJsonPath('data.unread_notifications', 0);
    }

    public function test_spending_in_a_past_month_does_not_trigger_alerts(): void
    {
        Notification::fake();
        Sanctum::actingAs($this->workspace->owner);

        $this->spend('300.00', '2026-10-05');

        Notification::assertNothingSent();
    }

    public function test_budget_status_reports_spent_remaining_and_percent(): void
    {
        Notification::fake();
        Sanctum::actingAs($this->workspace->owner);
        $this->spend('100.00');

        $this->getJson("/api/v1/workspaces/{$this->workspace->id}/budgets")
            ->assertOk()
            ->assertJsonPath('meta.month', '2026-11')
            ->assertJsonPath('data.0.spent', '100.00')
            ->assertJsonPath('data.0.remaining', '150.00')
            ->assertJsonPath('data.0.percent_used', 40);
    }

    public function test_only_one_budget_per_category_is_allowed(): void
    {
        Sanctum::actingAs($this->workspace->owner);

        $this->postJson("/api/v1/workspaces/{$this->workspace->id}/budgets", [
            'category_id' => $this->dining->id, 'amount' => '100', 'alert_thresholds' => [80],
        ])->assertJsonValidationErrors(['category_id' => 'A budget for this category already exists.']);
    }

    public function test_a_contributor_cannot_create_budgets(): void
    {
        Sanctum::actingAs($this->memberOf($this->workspace, WorkspaceRole::Contributor));

        $this->postJson("/api/v1/workspaces/{$this->workspace->id}/budgets", [
            'amount' => '100', 'alert_thresholds' => [80],
        ])->assertForbidden();
    }

    private function spend(string $amount, string $date = '2026-11-15'): void
    {
        $this->postJson("/api/v1/workspaces/{$this->workspace->id}/transactions", [
            'type' => 'expense',
            'account_id' => $this->account->id,
            'category_id' => $this->dining->id,
            'amount' => $amount,
            'transaction_date' => $date,
        ])->assertCreated();
    }
}
