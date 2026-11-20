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
     * @return array{income: string, expenses: string, net: string}
     */
    public function totals(Workspace $workspace, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $row = $workspace->transactions()
            ->whereBetween('transaction_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('COALESCE(SUM(CASE WHEN type = ? THEN amount END), 0) AS income', [TransactionType::Income->value])
            ->selectRaw('COALESCE(SUM(CASE WHEN type = ? THEN amount END), 0) AS expenses', [TransactionType::Expense->value])
            ->toBase()
            ->first();

        $income = BigDecimal::of($row->income);
        $expenses = BigDecimal::of($row->expenses);

        return [
            'income' => $this->format($income),
            'expenses' => $this->format($expenses),
            'net' => $this->format($income->minus($expenses)),
        ];
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
            ->selectRaw('SUM(CASE WHEN type = ? THEN amount ELSE -amount END) AS movement', [TransactionType::Income->value])
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
     * @return list<array{month: string, income: string, expenses: string}>
     */
    public function monthlyTrend(Workspace $workspace, CarbonImmutable $endMonth, int $months = 6): array
    {
        $start = $endMonth->startOfMonth()->subMonths($months - 1);

        $rows = $workspace->transactions()
            ->whereBetween('transaction_date', [$start->toDateString(), $endMonth->endOfMonth()->toDateString()])
            ->whereIn('type', [TransactionType::Income, TransactionType::Expense])
            ->selectRaw("DATE_FORMAT(transaction_date, '%Y-%m') AS month, type, SUM(amount) AS total")
            ->groupBy('month', 'type')
            ->toBase()
            ->get()
            ->groupBy('month');

        $trend = [];

        for ($i = 0; $i < $months; $i++) {
            $month = $start->addMonths($i)->format('Y-m');
            $byType = ($rows[$month] ?? collect())->pluck('total', 'type');

            $trend[] = [
                'month' => $month,
                'income' => $this->format(BigDecimal::of($byType[TransactionType::Income->value] ?? 0)),
                'expenses' => $this->format(BigDecimal::of($byType[TransactionType::Expense->value] ?? 0)),
            ];
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

    private function format(BigDecimal $amount): string
    {
        return (string) $amount->toScale(2, RoundingMode::HalfUp);
    }
}
