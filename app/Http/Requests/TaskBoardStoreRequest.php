<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TaskBoardStoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:60'],
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
            'name.required' => 'Please provide a board name.',
            'name.max' => 'Board names must be 60 characters or fewer.',
        ];
    }
}
