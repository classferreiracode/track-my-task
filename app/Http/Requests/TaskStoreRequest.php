<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskStoreRequest extends FormRequest
{
    /**
     * @var list<string>
     */
    private const PRIORITIES = [
        'baixa',
        'normal',
        'media',
        'alta',
        'urgente',
        'critico',
    ];

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:1000'],
            'priority' => ['sometimes', 'string', Rule::in(self::PRIORITIES)],
            'starts_at' => ['nullable', 'date_format:Y-m-d'],
            'ends_at' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:starts_at'],
            'task_board_id' => [
                'sometimes',
                'integer',
                Rule::exists('task_boards', 'id')->where(
                    'user_id',
                    $this->user()?->id,
                ),
            ],
            'task_column_id' => [
                'sometimes',
                'integer',
                Rule::exists('task_columns', 'id')->where(
                    'user_id',
                    $this->user()?->id,
                ),
            ],
            'labels' => ['sometimes', 'array'],
            'labels.*' => [
                'integer',
                Rule::exists('task_labels', 'id')->where(
                    'user_id',
                    $this->user()?->id,
                ),
            ],
            'tags' => ['sometimes', 'array'],
            'tags.*' => [
                'integer',
                Rule::exists('task_tags', 'id')->where(
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
            'title.required' => 'Please provide a task title.',
            'title.max' => 'Task titles must be 160 characters or fewer.',
            'description.max' => 'Descriptions must be 1000 characters or fewer.',
            'priority.in' => 'Please select a valid priority.',
            'starts_at.date_format' => 'Start date must use the YYYY-MM-DD format.',
            'ends_at.date_format' => 'End date must use the YYYY-MM-DD format.',
            'ends_at.after_or_equal' => 'End date must be on or after the start date.',
            'task_board_id.integer' => 'Please select a valid board.',
            'task_board_id.exists' => 'Please select a valid board.',
            'task_column_id.integer' => 'Please select a valid column.',
            'task_column_id.exists' => 'Please select a valid column.',
            'labels.array' => 'Labels must be an array.',
            'labels.*.integer' => 'Labels must be valid.',
            'labels.*.exists' => 'Labels must be valid.',
            'tags.array' => 'Tags must be an array.',
            'tags.*.integer' => 'Tags must be valid.',
            'tags.*.exists' => 'Tags must be valid.',
        ];
    }
}
