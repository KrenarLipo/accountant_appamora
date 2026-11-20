<?php

namespace App\Http\Requests\Api;

use App\Enums\TransactionType;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class SaveTransactionRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('recordTransactions', $this->route('workspace'));
    }

    /**
     * Accounts and categories must belong to the workspace in the URL.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $workspaceId = $this->route('workspace')->id;
        $type = TransactionType::tryFrom((string) $this->input('type'));

        $accountRule = Rule::exists('financial_accounts', 'id')
            ->where('workspace_id', $workspaceId)
            ->whereNull('deleted_at');

        return [
            'type' => ['required', Rule::enum(TransactionType::class)],
            'account_id' => ['required', 'integer', $accountRule],
            'transfer_account_id' => [
                Rule::requiredIf($type === TransactionType::Transfer),
                Rule::prohibitedIf($type !== TransactionType::Transfer),
                'nullable', 'integer', 'different:account_id', $accountRule,
            ],
            'category_id' => [
                Rule::prohibitedIf($type === TransactionType::Transfer),
                'nullable', 'integer',
                Rule::exists('categories', 'id')
                    ->where('workspace_id', $workspaceId)
                    ->where('type', $type?->categoryType()?->value)
                    ->whereNull('deleted_at'),
            ],
            'amount' => ['required', 'decimal:0,2', 'gt:0', 'max:999999999999'],
            'return_amount' => [
                Rule::prohibitedIf($type !== TransactionType::Expense),
                'nullable', 'decimal:0,2', 'gte:0', 'max:999999999999',
            ],
            'transaction_date' => ['required', 'date_format:Y-m-d'],
            'merchant' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category_id.exists' => 'Choose a category that matches the transaction type.',
            'transfer_account_id.different' => 'Choose a different account to transfer to.',
            'transfer_account_id.required' => 'Choose the account to transfer to.',
            'return_amount.prohibited' => 'Only expenses can have money earned back.',
        ];
    }
}
