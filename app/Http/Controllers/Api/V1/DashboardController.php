<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Workspace;
use App\Services\BudgetTracker;
use App\Services\FinancialSummary;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    public function show(Request $request, Workspace $workspace, FinancialSummary $summary, BudgetTracker $budgetTracker): JsonResponse
    {
        Gate::authorize('view', $workspace);

        $validated = $request->validate(['month' => ['nullable', 'date_format:Y-m']]);

        $month = isset($validated['month'])
            ? CarbonImmutable::createFromFormat('Y-m-d', $validated['month'].'-01', $workspace->timezone)
            : CarbonImmutable::now($workspace->timezone);

        $from = $month->startOfMonth();
        $to = $month->endOfMonth();
        $previous = $from->subMonth();

        return response()->json(['data' => [
            'period' => ['month' => $month->format('Y-m'), 'from' => $from->toDateString(), 'to' => $to->toDateString()],
            'currency' => $workspace->base_currency,
            'totals' => $summary->totals($workspace, $from, $to),
            'previous_totals' => $summary->totals($workspace, $previous, $previous->endOfMonth()),
            'accounts' => $summary->accountBalances($workspace),
            'trend' => $summary->monthlyTrend($workspace, $month),
            'expenses_by_category' => $summary->expensesByCategory($workspace, $from, $to),
            'top_merchants' => $summary->topMerchants($workspace, $from, $to),
            'budgets' => $budgetTracker->statuses($workspace, $month),
            'transaction_count' => $workspace->transactions()->count(),
        ]]);
    }
}
