<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskColumnOrderRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'task_board_id' => [
                'required',
                'integer',
                Rule::exists('task_boards', 'id'),
            ],
            'ordered_ids' => ['required', 'array', 'min:1'],
            'ordered_ids.*' => [
                'integer',
                Rule::exists('task_columns', 'id'),
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
            'task_board_id.required' => 'Please select a board.',
            'task_board_id.exists' => 'Please select a valid board.',
            'ordered_ids.required' => 'Please provide an ordering.',
            'ordered_ids.array' => 'Please provide a valid ordering.',
        ];
    }
}
