<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WorkspaceStoreRequest extends FormRequest
{
    /**
     * @var list<string>
     */
    private const PLANS = [
        'free',
        'pro',
        'enterprise',
    ];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'plan' => ['sometimes', 'string', 'in:'.implode(',', self::PLANS)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Please provide a workspace name.',
            'name.max' => 'Workspace names must be 100 characters or fewer.',
            'plan.in' => 'Please select a valid plan.',
        ];
    }
}
