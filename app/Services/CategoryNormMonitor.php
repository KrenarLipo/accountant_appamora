<?php

namespace App\Services;

use App\Enums\WorkspaceRole;
use App\Jobs\SendCategoryDeviationAlert;
use App\Models\Category;
use App\Models\User;
use App\Models\Workspace;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;

/**
 * Compares each category's monthly total with the "normal" amount the user set.
 * Going over is checked as soon as it happens; coming in under can only be judged once the month has ended.
 */
class CategoryNormMonitor
{
    public const OVER = 'over';

    public const UNDER = 'under';

    public function __construct(private FinancialSummary $summary) {}

    /**
     * @return list<array{category_id: int, normal: string, actual: string, tolerance: int, deviation_percent: float, status: string}>
     */
    public function statuses(Workspace $workspace, CarbonImmutable $month): array
    {
        $totals = $this->summary->categoryTotals($workspace, $month->startOfMonth(), $month->endOfMonth());
        $monthComplete = $month->endOfMonth()->lt(CarbonImmutable::now($workspace->timezone)->startOfDay());

        return $workspace->categories()
            ->whereNotNull('normal_amount')
            ->orderBy('id')
            ->get()
            ->map(fn (Category $category) => $this->evaluate($category, $totals[$category->id] ?? '0', $monthComplete))
            ->all();
    }

    /**
     * Create alerts for deviations not yet reported this month and dispatch them.
     *
     * @return int number of alerts created
     */
    public function check(Workspace $workspace, CarbonImmutable $month, bool $runAfterResponse = true): int
    {
        $period = $month->format('Y-m');
        $created = 0;

        foreach ($this->statuses($workspace, $month) as $status) {
            if (! in_array($status['status'], [self::OVER, self::UNDER], true)) {
                continue;
            }

            try {
                Category::find($status['category_id'])->alerts()->create([
                    'period' => $period,
                    'direction' => $status['status'],
                ]);
            } catch (UniqueConstraintViolationException) {
                continue;
            }

            $created++;
            $job = new SendCategoryDeviationAlert($workspace->id, $status['category_id'], $period, $status['status']);

            $runAfterResponse ? defer(fn () => dispatch_sync($job)) : dispatch_sync($job);
        }

        return $created;
    }

    /**
     * The figures for one deviation, ready to show to the user or to send to the AI.
     *
     * @return array<string, mixed>
     */
    public function facts(Workspace $workspace, Category $category, CarbonImmutable $month, string $direction): array
    {
        $from = $month->startOfMonth();
        $to = $month->endOfMonth();
        $total = $this->summary->categoryTotals($workspace, $from, $to)[$category->id] ?? '0.00';
        $status = $this->evaluate($category, $total, true);

        $previous = [];
        for ($i = 3; $i >= 1; $i--) {
            $start = $from->subMonths($i);
            $previous[] = [
                'month' => $start->format('Y-m'),
                'total' => $this->summary->categoryTotals($workspace, $start, $start->endOfMonth())[$category->id] ?? '0.00',
            ];
        }

        $largest = $category->transactions()
            ->whereBetween('transaction_date', [$from->toDateString(), $to->toDateString()])
            ->orderByDesc('amount')
            ->limit(5)
            ->get(['transaction_date', 'amount', 'merchant'])
            ->map(fn ($transaction) => [
                'date' => $transaction->transaction_date->toDateString(),
                'amount' => $this->summary->format(BigDecimal::of($transaction->amount)),
                'merchant' => $transaction->merchant,
            ])
            ->all();

        return [
            'workspace_type' => $workspace->type->value,
            'currency' => $workspace->base_currency,
            'category' => $category->name,
            'category_type' => $category->type->value,
            'month' => $month->format('Y-m'),
            'direction' => $direction,
            'normal_amount' => $status['normal'],
            'actual_amount' => $status['actual'],
            'allowed_difference_percent' => $status['tolerance'],
            'difference_percent' => $status['deviation_percent'],
            'previous_months' => $previous,
            'largest_transactions_this_month' => $largest,
        ];
    }

    /**
     * Owners, admins and finance managers receive deviation alerts.
     *
     * @return Collection<int, User>
     */
    public function recipients(Workspace $workspace): Collection
    {
        return $workspace->members()
            ->wherePivotIn('role', [WorkspaceRole::Owner->value, WorkspaceRole::Admin->value, WorkspaceRole::FinanceManager->value])
            ->get();
    }

    /**
     * @return array{category_id: int, normal: string, actual: string, tolerance: int, deviation_percent: float, status: string}
     */
    private function evaluate(Category $category, string $actual, bool $monthComplete): array
    {
        $normal = BigDecimal::of($category->normal_amount);
        $actualAmount = BigDecimal::of($actual);
        $tolerance = (int) $category->normal_tolerance;

        $deviation = $normal->isZero()
            ? 0.0
            : $actualAmount->minus($normal)->multipliedBy(100)->dividedBy($normal, 1, RoundingMode::HalfUp)->toFloat();

        $status = match (true) {
            $deviation > $tolerance => self::OVER,
            $deviation < -$tolerance && $monthComplete => self::UNDER,
            $deviation < -$tolerance => 'below_so_far',
            default => 'normal',
        };

        return [
            'category_id' => $category->id,
            'normal' => $this->summary->format($normal),
            'actual' => $this->summary->format($actualAmount),
            'tolerance' => $tolerance,
            'deviation_percent' => $deviation,
            'status' => $status,
        ];
    }
}
