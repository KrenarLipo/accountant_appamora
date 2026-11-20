<?php

namespace App\Http\Requests\Api;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateWorkspaceRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('update', $this->route('workspace'));
    }

    /**
     * Base currency is fixed after creation so historic totals stay consistent.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'timezone' => ['required', 'timezone:all'],
            'country' => ['nullable', 'string', 'size:2', 'alpha'],
        ];
    }
}
