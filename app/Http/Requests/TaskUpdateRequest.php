<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:160'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'is_completed' => ['sometimes', 'boolean'],
            'task_column_id' => [
                'sometimes',
                'integer',
                Rule::exists('task_columns', 'id')->where(
                    'user_id',
                    $this->user()?->id,
                ),
            ],
        ];
    }

    /**
     * Get custom error messages for validator failures.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.max' => 'Task titles must be 160 characters or fewer.',
            'description.max' => 'Descriptions must be 1000 characters or fewer.',
            'is_completed.boolean' => 'Completion status must be true or false.',
            'task_column_id.integer' => 'Please select a valid column.',
            'task_column_id.exists' => 'Please select a valid column.',
        ];
    }
}
