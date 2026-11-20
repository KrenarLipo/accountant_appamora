<?php

namespace App\Http\Requests\Api;

use App\Enums\CategoryType;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveBudgetRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('manageFinances', $this->route('workspace'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id' => [
                'nullable', 'integer',
                Rule::exists('categories', 'id')
                    ->where('workspace_id', $this->route('workspace')->id)
                    ->where('type', CategoryType::Expense->value)
                    ->whereNull('deleted_at'),
            ],
            'amount' => ['required', 'decimal:0,2', 'gt:0', 'max:999999999999'],
            'alert_thresholds' => ['required', 'array', 'min:1', 'max:5'],
            'alert_thresholds.*' => ['integer', 'between:1,200', 'distinct'],
        ];
    }

    /**
     * One budget per category, and one overall budget, per workspace.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $exists = $this->route('workspace')->budgets()
                    ->where('category_id', $this->input('category_id'))
                    ->when($this->route('budget'), fn ($query, $budget) => $query->whereKeyNot($budget->id))
                    ->exists();

                if ($exists) {
                    $validator->errors()->add('category_id', 'A budget for this category already exists.');
                }
            },
        ];
    }
}
