<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskReorderRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'column_id' => [
                'required',
                'integer',
                Rule::exists('task_columns', 'id')->where(
                    'user_id',
                    $this->user()?->id,
                ),
            ],
            'ordered_ids' => ['required', 'array', 'min:1'],
            'ordered_ids.*' => [
                'integer',
                Rule::exists('tasks', 'id')->where(
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
            'column_id.required' => 'Please select a column.',
            'column_id.exists' => 'Please select a valid column.',
            'ordered_ids.required' => 'Please provide an ordering.',
            'ordered_ids.array' => 'Please provide a valid ordering.',
        ];
    }
}
