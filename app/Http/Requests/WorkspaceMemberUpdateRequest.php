<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WorkspaceMemberUpdateRequest extends FormRequest
{
    /**
     * @var list<string>
     */
    private const ROLES = [
        'owner',
        'admin',
        'member',
        'editor',
        'viewer',
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
            'role' => ['sometimes', 'string', 'in:'.implode(',', self::ROLES)],
            'weekly_capacity_minutes' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'role.in' => 'Please select a valid role.',
            'weekly_capacity_minutes.integer' => 'Capacity must be a number.',
            'weekly_capacity_minutes.min' => 'Capacity must be zero or more.',
            'is_active.boolean' => 'Status must be true or false.',
        ];
    }
}
