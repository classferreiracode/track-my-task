<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskReportRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'start' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'end' => ['required', 'date_format:Y-m-d', 'after_or_equal:start'],
            'task_board_id' => [
                'sometimes',
                'integer',
                Rule::exists('task_boards', 'id'),
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
            'start.required' => 'Please select a start date.',
            'start.date_format' => 'Start date must use the YYYY-MM-DD format.',
            'start.before_or_equal' => 'Start date cannot be in the future.',
            'end.required' => 'Please select an end date.',
            'end.date_format' => 'End date must use the YYYY-MM-DD format.',
            'end.after_or_equal' => 'End date must be on or after the start date.',
            'task_board_id.integer' => 'Please select a valid board.',
            'task_board_id.exists' => 'Please select a valid board.',
        ];
    }
}
