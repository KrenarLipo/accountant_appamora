<?php

namespace App\Http\Requests\Api;

use App\Enums\AccountType;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class SaveAccountRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::enum(AccountType::class)],
            'opening_balance' => ['required', 'decimal:0,2', 'between:-999999999999,999999999999'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
