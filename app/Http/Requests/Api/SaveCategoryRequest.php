<?php

namespace App\Http\Requests\Api;

use App\Enums\CategoryType;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class SaveCategoryRequest extends FormRequest
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
        $workspace = $this->route('workspace');
        $category = $this->route('category');

        return [
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('categories')
                    ->where('workspace_id', $workspace->id)
                    ->where('type', $this->input('type'))
                    ->whereNull('deleted_at')
                    ->ignore($category),
            ],
            'type' => ['required', Rule::enum(CategoryType::class)],
            'color' => ['nullable', 'hex_color'],
            'parent_id' => [
                'nullable', 'integer',
                Rule::exists('categories', 'id')
                    ->where('workspace_id', $workspace->id)
                    ->where('type', $this->input('type'))
                    ->whereNull('parent_id')
                    ->whereNull('deleted_at'),
                Rule::notIn(array_filter([$category?->id])),
            ],
        ];
    }
}
