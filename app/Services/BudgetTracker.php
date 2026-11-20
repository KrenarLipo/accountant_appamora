<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\Budget;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\BudgetThresholdReached;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;

class BudgetTracker
{
    /**
     * Spending against every monthly budget for the month containing $month.
     *
     * @return list<array{id: int, category_id: int|null, category_name: string, amount: string, spent: string, remaining: string, percent_used: float, alert_thresholds: list<int>}>
     */
    public function statuses(Workspace $workspace, CarbonImmutable $month): array
    {
        return $this->evaluate($workspace, $month)
            ->map(fn (array $item) => $item['status'])
            ->values()
            ->all();
    }

    /**
     * Create one alert per budget, month and threshold, and notify finance managers.
     */
    public function checkAlerts(Workspace $workspace, CarbonImmutable $month): void
    {
        $period = $month->format('Y-m');
        $currentMonth = CarbonImmutable::now($workspace->timezone)->format('Y-m');

        if ($period !== $currentMonth) {
            return;
        }

        foreach ($this->evaluate($workspace, $month) as $item) {
            /** @var Budget $budget */
            $budget = $item['budget'];
            $percentUsed = $item['status']['percent_used'];

            $crossed = collect($budget->alert_thresholds)
                ->filter(fn (int $threshold) => $percentUsed >= $threshold)
                ->sort()
                ->values();

            if ($crossed->isEmpty()) {
                continue;
            }

            $alreadySent = $budget->alerts()->where('period', $period)->pluck('threshold');
            $newThresholds = $crossed->diff($alreadySent);

            if ($newThresholds->isEmpty()) {
                continue;
            }

            foreach ($newThresholds as $threshold) {
                try {
                    $budget->alerts()->create(['period' => $period, 'threshold' => $threshold]);
                } catch (UniqueConstraintViolationException) {
                    $newThresholds = $newThresholds->reject(fn (int $value) => $value === $threshold);
                }
            }

            if ($newThresholds->isEmpty()) {
                continue;
            }

            $this->financeRecipients($workspace)->each(
                fn (User $user) => $user->notify(new BudgetThresholdReached(
                    $workspace,
                    $item['status'],
                    (int) $newThresholds->max(),
                    $period,
                ))
            );
        }
    }

    /**
     * @return Collection<int, array{budget: Budget, status: array{id: int, category_id: int|null, category_name: string, amount: string, spent: string, remaining: string, percent_used: float, alert_thresholds: list<int>}}>
     */
    private function evaluate(Workspace $workspace, CarbonImmutable $month): Collection
    {
        $from = $month->startOfMonth()->toDateString();
        $to = $month->endOfMonth()->toDateString();

        $spentByCategory = $workspace->transactions()
            ->where('type', TransactionType::Expense)
            ->whereBetween('transaction_date', [$from, $to])
            ->selectRaw('category_id, SUM(amount) AS total')
            ->groupBy('category_id')
            ->toBase()
            ->pluck('total', 'category_id');

        $totalSpent = $spentByCategory->reduce(
            fn (BigDecimal $carry, $value) => $carry->plus($value),
            BigDecimal::zero(),
        );

        return $workspace->budgets()
            ->with('category')
            ->orderByRaw('category_id IS NOT NULL')
            ->orderBy('id')
            ->get()
            ->map(function (Budget $budget) use ($spentByCategory, $totalSpent): array {
                $amount = BigDecimal::of($budget->amount);
                $spent = $budget->category_id === null
                    ? $totalSpent
                    : BigDecimal::of($spentByCategory[$budget->category_id] ?? 0);

                $percentUsed = $amount->isZero()
                    ? 0.0
                    : $spent->multipliedBy(100)->dividedBy($amount, 1, RoundingMode::HalfUp)->toFloat();

                return [
                    'budget' => $budget,
                    'status' => [
                        'id' => $budget->id,
                        'category_id' => $budget->category_id,
                        'category_name' => $budget->category?->name ?? 'All expenses',
                        'amount' => (string) $amount->toScale(2, RoundingMode::HalfUp),
                        'spent' => (string) $spent->toScale(2, RoundingMode::HalfUp),
                        'remaining' => (string) $amount->minus($spent)->toScale(2, RoundingMode::HalfUp),
                        'percent_used' => $percentUsed,
                        'alert_thresholds' => array_map('intval', $budget->alert_thresholds),
                    ],
                ];
            });
    }

    /**
     * @return Collection<int, User>
     */
    private function financeRecipients(Workspace $workspace): Collection
    {
        return $workspace->members()
            ->wherePivotIn('role', ['owner', 'admin', 'finance_manager'])
            ->get();
    }
}
