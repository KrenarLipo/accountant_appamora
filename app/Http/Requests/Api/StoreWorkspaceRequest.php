<?php

namespace App\Http\Requests\Api;

use App\Enums\WorkspaceType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkspaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::enum(WorkspaceType::class)],
            'base_currency' => ['required', 'string', 'size:3', 'alpha'],
            'timezone' => ['required', 'timezone:all'],
            'country' => ['nullable', 'string', 'size:2', 'alpha'],
            'account_name' => ['nullable', 'string', 'max:100'],
            'opening_balance' => ['nullable', 'decimal:0,2', 'between:-999999999999,999999999999'],
        ];
    }
}
