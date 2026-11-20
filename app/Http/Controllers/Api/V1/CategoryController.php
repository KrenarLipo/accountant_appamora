<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CategoryType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SaveCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Models\Workspace;
use App\Services\CategoryNormMonitor;
use App\Services\FinancialSummary;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    /**
     * Categories with this month's total and how it compares with the normal amount.
     */
    public function index(Workspace $workspace, FinancialSummary $summary, CategoryNormMonitor $normMonitor): AnonymousResourceCollection
    {
        Gate::authorize('view', $workspace);

        $month = CarbonImmutable::now($workspace->timezone);
        $totals = $summary->categoryTotals($workspace, $month->startOfMonth(), $month->endOfMonth());
        $norms = collect($normMonitor->statuses($workspace, $month))->keyBy('category_id');

        $categories = $workspace->categories()->withCount('transactions')->orderBy('type')->orderBy('name')->get()
            ->each(function (Category $category) use ($totals, $norms): void {
                $category->setAttribute('month_total', $totals[$category->id] ?? '0.00');
                $category->setAttribute('norm', $norms->get($category->id));
            });

        return CategoryResource::collection($categories)->additional(['meta' => ['month' => $month->format('Y-m')]]);
    }

    public function store(SaveCategoryRequest $request, Workspace $workspace): CategoryResource
    {
        return new CategoryResource($workspace->categories()->create($request->validated()));
    }

    public function update(SaveCategoryRequest $request, Workspace $workspace, Category $category): CategoryResource|JsonResponse
    {
        if ($request->enum('type', CategoryType::class) !== $category->type && $category->transactions()->exists()) {
            return response()->json(['message' => 'A category with transactions cannot change between income and expense.'], 422);
        }

        $category->update($request->validated());

        return new CategoryResource($category);
    }

    /**
     * A category still used by transactions must be reassigned before it can be deleted.
     */
    public function destroy(Request $request, Workspace $workspace, Category $category): Response|JsonResponse
    {
        Gate::authorize('manageFinances', $workspace);

        $inUse = $category->transactions()->exists();

        $validated = $request->validate([
            'reassign_to' => [
                Rule::requiredIf($inUse), 'nullable', 'integer', Rule::notIn([$category->id]),
                Rule::exists('categories', 'id')
                    ->where('workspace_id', $workspace->id)
                    ->where('type', $category->type->value)
                    ->whereNull('deleted_at'),
            ],
        ], [
            'reassign_to.required' => 'This category has transactions. Choose another category to move them to.',
        ]);

        DB::transaction(function () use ($workspace, $category, $validated): void {
            if (! empty($validated['reassign_to'])) {
                $category->transactions()->each(
                    fn ($transaction) => $transaction->update(['category_id' => $validated['reassign_to']])
                );
                $workspace->budgets()->where('category_id', $category->id)->delete();
            }

            $category->delete();
        });

        return response()->noContent();
    }
}
