<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SaveAccountRequest;
use App\Http\Resources\AccountResource;
use App\Models\FinancialAccount;
use App\Models\Workspace;
use App\Services\FinancialSummary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class AccountController extends Controller
{
    public function index(Workspace $workspace, FinancialSummary $summary): JsonResponse
    {
        Gate::authorize('view', $workspace);

        return response()->json(['data' => $summary->accountBalances($workspace)]);
    }

    public function store(SaveAccountRequest $request, Workspace $workspace): AccountResource
    {
        $account = $workspace->accounts()->create([
            ...$request->validated(),
            'currency' => $workspace->base_currency,
        ]);

        return new AccountResource($account);
    }

    public function update(SaveAccountRequest $request, Workspace $workspace, FinancialAccount $account): AccountResource
    {
        $account->update($request->validated());

        return new AccountResource($account);
    }

    /**
     * Soft-deletes the account; its past transactions remain in reports.
     */
    public function destroy(Workspace $workspace, FinancialAccount $account): Response|JsonResponse
    {
        Gate::authorize('manageFinances', $workspace);

        if ($workspace->accounts()->count() <= 1) {
            return response()->json(['message' => 'A workspace needs at least one account.'], 422);
        }

        $account->delete();

        return response()->noContent();
    }
}
