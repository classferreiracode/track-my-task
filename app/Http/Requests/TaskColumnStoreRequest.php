<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TaskColumnStoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:40'],
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
            'name.required' => 'Please provide a column name.',
            'name.max' => 'Column names must be 40 characters or fewer.',
        ];
    }
}
