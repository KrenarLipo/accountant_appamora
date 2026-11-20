<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Workspace;
use App\Services\FinancialSummary;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CalendarController extends Controller
{
    /**
     * Daily income, expenses, returns and profit/loss for one month.
     */
    public function show(Request $request, Workspace $workspace, FinancialSummary $summary): JsonResponse
    {
        Gate::authorize('view', $workspace);

        $validated = $request->validate(['month' => ['nullable', 'date_format:Y-m']]);
        $month = isset($validated['month'])
            ? CarbonImmutable::createFromFormat('Y-m-d', $validated['month'].'-01', $workspace->timezone)
            : CarbonImmutable::now($workspace->timezone);

        $from = $month->startOfMonth();
        $to = $month->endOfMonth();

        return response()->json(['data' => [
            'month' => $month->format('Y-m'),
            'today' => CarbonImmutable::now($workspace->timezone)->toDateString(),
            'currency' => $workspace->base_currency,
            'days' => $summary->calendar($workspace, $from, $to),
            'totals' => $summary->totals($workspace, $from, $to),
        ]]);
    }
}
