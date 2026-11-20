<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SaveTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Models\Workspace;
use App\Services\BudgetTracker;
use App\Services\CategoryNormMonitor;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class TransactionController extends Controller
{
    public function __construct(
        private BudgetTracker $budgetTracker,
        private CategoryNormMonitor $normMonitor,
    ) {}

    public function index(Request $request, Workspace $workspace): AnonymousResourceCollection
    {
        Gate::authorize('view', $workspace);

        $filters = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'type' => ['nullable', Rule::enum(TransactionType::class)],
            'account_id' => ['nullable', 'integer'],
            'category_id' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'between:5,100'],
        ]);

        $transactions = $workspace->transactions()
            ->with(['account', 'transferAccount', 'category', 'creator'])
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->where('transaction_date', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->where('transaction_date', '<=', $to))
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when($filters['account_id'] ?? null, fn ($query, $accountId) => $query->where(
                fn ($inner) => $inner->where('account_id', $accountId)->orWhere('transfer_account_id', $accountId)
            ))
            ->when($filters['category_id'] ?? null, fn ($query, $categoryId) => $query->where('category_id', $categoryId))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(
                fn ($inner) => $inner->where('merchant', 'like', "%{$search}%")->orWhere('description', 'like', "%{$search}%")
            ))
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate($filters['per_page'] ?? 25)
            ->withQueryString();

        return TransactionResource::collection($transactions);
    }

    public function store(SaveTransactionRequest $request, Workspace $workspace): TransactionResource
    {
        $transaction = new Transaction($request->validated());
        $transaction->workspace()->associate($workspace);
        $transaction->currency = $workspace->base_currency;
        $transaction->created_by = $request->user()->id;
        $transaction->save();

        $this->checkBudgets($workspace, $transaction->transaction_date);

        return new TransactionResource($transaction->load(['account', 'transferAccount', 'category', 'creator']));
    }

    public function show(Workspace $workspace, Transaction $transaction): TransactionResource
    {
        Gate::authorize('view', $workspace);

        return new TransactionResource($transaction->load(['account', 'transferAccount', 'category', 'creator']));
    }

    public function update(SaveTransactionRequest $request, Workspace $workspace, Transaction $transaction): TransactionResource
    {
        $previousDate = $transaction->transaction_date;

        $transaction->fill([
            'transfer_account_id' => null,
            'category_id' => null,
            ...$request->validated(),
        ]);
        $transaction->updated_by = $request->user()->id;
        $transaction->save();

        $this->checkBudgets($workspace, $transaction->transaction_date);

        if (! $previousDate->isSameMonth($transaction->transaction_date)) {
            $this->checkBudgets($workspace, $previousDate);
        }

        return new TransactionResource($transaction->load(['account', 'transferAccount', 'category', 'creator']));
    }

    public function destroy(Workspace $workspace, Transaction $transaction): Response
    {
        Gate::authorize('recordTransactions', $workspace);

        $transaction->delete();

        $this->checkBudgets($workspace, $transaction->transaction_date);

        return response()->noContent();
    }

    private function checkBudgets(Workspace $workspace, \DateTimeInterface $date): void
    {
        $month = CarbonImmutable::instance($date);

        $this->budgetTracker->checkAlerts($workspace, $month);
        $this->normMonitor->check($workspace, $month);
    }
}
