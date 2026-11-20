<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\Workspace;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Deterministic cash-basis figures for a workspace. Transfers never count as income or expenses.
 */
class FinancialSummary
{
    /**
     * Net result = income + money earned back on expenses - expenses (cash-basis profit or loss).
     *
     * @return array{income: string, expenses: string, returns: string, net: string}
     */
    public function totals(Workspace $workspace, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $row = $workspace->transactions()
            ->whereBetween('transaction_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('COALESCE(SUM(CASE WHEN type = ? THEN amount END), 0) AS income', [TransactionType::Income->value])
            ->selectRaw('COALESCE(SUM(CASE WHEN type = ? THEN amount END), 0) AS expenses', [TransactionType::Expense->value])
            ->selectRaw('COALESCE(SUM(CASE WHEN type = ? THEN return_amount END), 0) AS returns', [TransactionType::Expense->value])
            ->toBase()
            ->first();

        return $this->result($row->income, $row->expenses, $row->returns);
    }

    /**
     * Income, expenses, returns and net result for every day in the range that has activity.
     *
     * @return list<array{date: string, income: string, expenses: string, returns: string, net: string, count: int}>
     */
    public function calendar(Workspace $workspace, CarbonImmutable $from, CarbonImmutable $to): array
    {
        return $workspace->transactions()
            ->whereBetween('transaction_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('transaction_date')
            ->selectRaw('COALESCE(SUM(CASE WHEN type = ? THEN amount END), 0) AS income', [TransactionType::Income->value])
            ->selectRaw('COALESCE(SUM(CASE WHEN type = ? THEN amount END), 0) AS expenses', [TransactionType::Expense->value])
            ->selectRaw('COALESCE(SUM(CASE WHEN type = ? THEN return_amount END), 0) AS returns', [TransactionType::Expense->value])
            ->selectRaw('COUNT(*) AS count')
            ->groupBy('transaction_date')
            ->orderBy('transaction_date')
            ->toBase()
            ->get()
            ->map(fn ($row) => [
                'date' => substr((string) $row->transaction_date, 0, 10),
                ...$this->result($row->income, $row->expenses, $row->returns),
                'count' => (int) $row->count,
            ])
            ->all();
    }

    /**
     * Total recorded per category (income and expense categories) in the range.
     *
     * @return array<int, string>
     */
    public function categoryTotals(Workspace $workspace, CarbonImmutable $from, CarbonImmutable $to): array
    {
        return $workspace->transactions()
            ->whereIn('type', [TransactionType::Income, TransactionType::Expense])
            ->whereNotNull('category_id')
            ->whereBetween('transaction_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('category_id, SUM(amount) AS total')
            ->groupBy('category_id')
            ->toBase()
            ->pluck('total', 'category_id')
            ->map(fn ($total) => $this->format(BigDecimal::of($total)))
            ->all();
    }

    /**
     * Opening balance plus every posted movement, with transfers applied to both accounts.
     *
     * @return list<array{id: int, name: string, type: string, currency: string, is_active: bool, opening_balance: string, balance: string}>
     */
    public function accountBalances(Workspace $workspace): array
    {
        $movements = $workspace->transactions()
            ->selectRaw('account_id')
            ->selectRaw('SUM(CASE WHEN type = ? THEN amount ELSE -amount END) + SUM(COALESCE(return_amount, 0)) AS movement', [TransactionType::Income->value])
            ->groupBy('account_id')
            ->toBase()
            ->pluck('movement', 'account_id');

        $transfersIn = $workspace->transactions()
            ->where('type', TransactionType::Transfer)
            ->selectRaw('transfer_account_id, SUM(amount) AS movement')
            ->groupBy('transfer_account_id')
            ->toBase()
            ->pluck('movement', 'transfer_account_id');

        return $workspace->accounts()
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get()
            ->map(fn ($account) => [
                'id' => $account->id,
                'name' => $account->name,
                'type' => $account->type->value,
                'currency' => $account->currency,
                'is_active' => $account->is_active,
                'opening_balance' => $this->format(BigDecimal::of($account->opening_balance)),
                'balance' => $this->format(
                    BigDecimal::of($account->opening_balance)
                        ->plus($movements[$account->id] ?? 0)
                        ->plus($transfersIn[$account->id] ?? 0)
                ),
            ])
            ->all();
    }

    /**
     * Income and expenses for each of the last N months, oldest first.
     *
     * @return list<array{month: string, income: string, expenses: string, returns: string, net: string}>
     */
    public function monthlyTrend(Workspace $workspace, CarbonImmutable $endMonth, int $months = 6): array
    {
        $start = $endMonth->startOfMonth()->subMonths($months - 1);

        $rows = $workspace->transactions()
            ->whereBetween('transaction_date', [$start->toDateString(), $endMonth->endOfMonth()->toDateString()])
            ->selectRaw("DATE_FORMAT(transaction_date, '%Y-%m') AS month")
            ->selectRaw('COALESCE(SUM(CASE WHEN type = ? THEN amount END), 0) AS income', [TransactionType::Income->value])
            ->selectRaw('COALESCE(SUM(CASE WHEN type = ? THEN amount END), 0) AS expenses', [TransactionType::Expense->value])
            ->selectRaw('COALESCE(SUM(CASE WHEN type = ? THEN return_amount END), 0) AS returns', [TransactionType::Expense->value])
            ->groupBy('month')
            ->toBase()
            ->get()
            ->keyBy('month');

        $trend = [];

        for ($i = 0; $i < $months; $i++) {
            $month = $start->addMonths($i)->format('Y-m');
            $row = $rows[$month] ?? null;

            $trend[] = ['month' => $month, ...$this->result($row->income ?? 0, $row->expenses ?? 0, $row->returns ?? 0)];
        }

        return $trend;
    }

    /**
     * @return list<array{category_id: int|null, name: string, color: string|null, total: string}>
     */
    public function expensesByCategory(Workspace $workspace, CarbonImmutable $from, CarbonImmutable $to): array
    {
        return DB::table('transactions')
            ->leftJoin('categories', 'categories.id', '=', 'transactions.category_id')
            ->where('transactions.workspace_id', $workspace->id)
            ->whereNull('transactions.deleted_at')
            ->where('transactions.type', TransactionType::Expense->value)
            ->whereBetween('transactions.transaction_date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('transactions.category_id', 'categories.name', 'categories.color')
            ->selectRaw('transactions.category_id, categories.name, categories.color, SUM(transactions.amount) AS total')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'category_id' => $row->category_id,
                'name' => $row->name ?? 'Uncategorized',
                'color' => $row->color,
                'total' => $this->format(BigDecimal::of($row->total)),
            ])
            ->all();
    }

    /**
     * @return list<array{merchant: string, total: string, count: int}>
     */
    public function topMerchants(Workspace $workspace, CarbonImmutable $from, CarbonImmutable $to, int $limit = 5): array
    {
        return $workspace->transactions()
            ->where('type', TransactionType::Expense)
            ->whereNotNull('merchant')
            ->whereBetween('transaction_date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('merchant')
            ->selectRaw('merchant, SUM(amount) AS total, COUNT(*) AS count')
            ->orderByDesc('total')
            ->limit($limit)
            ->toBase()
            ->get()
            ->map(fn ($row) => [
                'merchant' => $row->merchant,
                'total' => $this->format(BigDecimal::of($row->total)),
                'count' => (int) $row->count,
            ])
            ->all();
    }

    /**
     * @return array{income: string, expenses: string, returns: string, net: string}
     */
    private function result(mixed $income, mixed $expenses, mixed $returns): array
    {
        $income = BigDecimal::of($income);
        $expenses = BigDecimal::of($expenses);
        $returns = BigDecimal::of($returns);

        return [
            'income' => $this->format($income),
            'expenses' => $this->format($expenses),
            'returns' => $this->format($returns),
            'net' => $this->format($income->plus($returns)->minus($expenses)),
        ];
    }

    public function format(BigDecimal $amount): string
    {
        return (string) $amount->toScale(2, RoundingMode::HalfUp);
    }
}
