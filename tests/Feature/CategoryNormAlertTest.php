<?php

namespace Tests\Feature;

use App\Contracts\ExplainsAlerts;
use App\Enums\WorkspaceRole;
use App\Models\Category;
use App\Models\FinancialAccount;
use App\Models\Transaction;
use App\Models\Workspace;
use App\Notifications\CategoryDeviationDetected;
use App\Services\Ai\ClaudeAlertExplainer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CategoryNormAlertTest extends TestCase
{
    use RefreshDatabase;

    private Workspace $workspace;

    private FinancialAccount $account;

    private Category $marketing;

    /** @var list<array<string, mixed>> */
    private array $explainedFacts = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-11-20 12:00:00');
        Notification::fake();

        $this->workspace = Workspace::factory()->business()->create(['ai_insights_enabled' => true]);
        $this->account = FinancialAccount::factory()->for($this->workspace)->create();
        $this->marketing = Category::factory()->for($this->workspace)->create([
            'name' => 'Marketing', 'normal_amount' => '500.00', 'normal_tolerance' => 20,
        ]);

        $test = $this;
        $this->app->instance(ExplainsAlerts::class, new class($test) implements ExplainsAlerts
        {
            public function __construct(private CategoryNormAlertTest $test) {}

            public function explain(Workspace $workspace, array $facts): ?string
            {
                $this->test->recordExplanation($facts);

                return 'Marketing is EUR 650.00 against a normal EUR 500.00; check the ads payment.';
            }

            public function isAvailable(): bool
            {
                return true;
            }
        });
    }

    public function recordExplanation(array $facts): void
    {
        $this->explainedFacts[] = $facts;
    }

    public function test_the_normal_amount_can_be_set_on_a_category(): void
    {
        Sanctum::actingAs($this->workspace->owner);

        $this->putJson("/api/v1/workspaces/{$this->workspace->id}/categories/{$this->marketing->id}", [
            'name' => 'Marketing', 'type' => 'expense', 'normal_amount' => '800.00', 'normal_tolerance' => 15,
        ])->assertOk()
            ->assertJsonPath('data.normal_amount', '800.0000')
            ->assertJsonPath('data.normal_tolerance', 15);
    }

    public function test_going_over_the_allowed_difference_sends_one_alert_with_the_ai_explanation(): void
    {
        $manager = $this->memberOf($this->workspace, WorkspaceRole::FinanceManager);
        $viewer = $this->memberOf($this->workspace, WorkspaceRole::Viewer);
        Sanctum::actingAs($this->workspace->owner);

        $this->spend('590.00'); // +18%: within ±20%
        Notification::assertNothingSent();

        $this->spend('60.00');  // 650 = +30%
        $this->spend('10.00');  // still over: no second alert

        Notification::assertSentToTimes($this->workspace->owner, CategoryDeviationDetected::class, 1);
        Notification::assertSentTo($manager, CategoryDeviationDetected::class, function (CategoryDeviationDetected $notification) {
            $data = $notification->toArray($this->workspace->owner);

            return $data['title'] === 'Marketing is higher than normal'
                && $data['ai_explanation'] === 'Marketing is EUR 650.00 against a normal EUR 500.00; check the ads payment.'
                && str_contains($data['message'], '30% above your normal');
        });
        Notification::assertNotSentTo($viewer, CategoryDeviationDetected::class);

        $facts = $this->explainedFacts[0];
        $this->assertSame('650.00', $facts['actual_amount']);
        $this->assertSame('500.00', $facts['normal_amount']);
        $this->assertSame(30.0, $facts['difference_percent']);
        $this->assertSame('EUR', $facts['currency']);
        $this->assertCount(2, $facts['largest_transactions_this_month']);
    }

    public function test_being_below_normal_is_only_reported_after_the_month_ends(): void
    {
        $this->addSpending('100.00', '2026-10-10'); // October: 80% below normal
        $this->addSpending('100.00', '2026-11-10'); // November so far: also low, but month not over

        $this->artisan('accountant:check-category-norms')->assertSuccessful();

        Notification::assertSentToTimes($this->workspace->owner, CategoryDeviationDetected::class, 1);
        Notification::assertSentTo($this->workspace->owner, CategoryDeviationDetected::class, function (CategoryDeviationDetected $notification) {
            $data = $notification->toArray($this->workspace->owner);

            return $data['period'] === '2026-10' && $data['title'] === 'Marketing was lower than normal';
        });

        $this->artisan('accountant:check-category-norms')->assertSuccessful();
        Notification::assertSentToTimes($this->workspace->owner, CategoryDeviationDetected::class, 1);
    }

    public function test_the_alert_falls_back_to_plain_wording_without_an_api_key(): void
    {
        $this->app->forgetInstance(ExplainsAlerts::class);
        $this->app->bind(ExplainsAlerts::class, ClaudeAlertExplainer::class);
        config(['services.anthropic.key' => null]);
        Sanctum::actingAs($this->workspace->owner);

        $this->spend('700.00');

        Notification::assertSentTo($this->workspace->owner, CategoryDeviationDetected::class, function (CategoryDeviationDetected $notification) {
            return $notification->aiExplanation === null
                && $notification->toArray($this->workspace->owner)['message'] !== '';
        });
    }

    public function test_the_categories_list_shows_this_months_total_against_normal(): void
    {
        $this->addSpending('550.00', '2026-11-05');
        Sanctum::actingAs($this->workspace->owner);

        $this->getJson("/api/v1/workspaces/{$this->workspace->id}/categories")
            ->assertOk()
            ->assertJsonPath('data.0.month_total', '550.00')
            ->assertJsonPath('data.0.norm.deviation_percent', 10)
            ->assertJsonPath('data.0.norm.status', 'normal');
    }

    private function spend(string $amount): void
    {
        $this->postJson("/api/v1/workspaces/{$this->workspace->id}/transactions", [
            'type' => 'expense', 'account_id' => $this->account->id, 'category_id' => $this->marketing->id,
            'amount' => $amount, 'transaction_date' => '2026-11-15', 'merchant' => 'Ads',
        ])->assertCreated();
    }

    private function addSpending(string $amount, string $date): void
    {
        Transaction::factory()->for($this->workspace)->create([
            'account_id' => $this->account->id, 'category_id' => $this->marketing->id,
            'amount' => $amount, 'transaction_date' => $date,
        ]);
    }
}
