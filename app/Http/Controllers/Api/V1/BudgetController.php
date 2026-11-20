<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SaveBudgetRequest;
use App\Models\Budget;
use App\Models\Workspace;
use App\Services\BudgetTracker;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class BudgetController extends Controller
{
    public function __construct(private BudgetTracker $budgetTracker) {}

    public function index(Request $request, Workspace $workspace): JsonResponse
    {
        Gate::authorize('view', $workspace);

        $month = $this->month($request, $workspace);

        return response()->json([
            'data' => $this->budgetTracker->statuses($workspace, $month),
            'meta' => ['month' => $month->format('Y-m'), 'currency' => $workspace->base_currency],
        ]);
    }

    public function store(SaveBudgetRequest $request, Workspace $workspace): JsonResponse
    {
        $budget = $workspace->budgets()->create($request->validated());

        $this->budgetTracker->checkAlerts($workspace, CarbonImmutable::now($workspace->timezone));

        return response()->json(['data' => ['id' => $budget->id]], 201);
    }

    public function update(SaveBudgetRequest $request, Workspace $workspace, Budget $budget): JsonResponse
    {
        $budget->update($request->validated());

        $this->budgetTracker->checkAlerts($workspace, CarbonImmutable::now($workspace->timezone));

        return response()->json(['data' => ['id' => $budget->id]]);
    }

    public function destroy(Workspace $workspace, Budget $budget): Response
    {
        Gate::authorize('manageFinances', $workspace);

        $budget->delete();

        return response()->noContent();
    }

    private function month(Request $request, Workspace $workspace): CarbonImmutable
    {
        $validated = $request->validate(['month' => ['nullable', 'date_format:Y-m']]);

        return isset($validated['month'])
            ? CarbonImmutable::createFromFormat('Y-m-d', $validated['month'].'-01', $workspace->timezone)
            : CarbonImmutable::now($workspace->timezone);
    }
}
